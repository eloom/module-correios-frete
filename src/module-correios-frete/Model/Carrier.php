<?php
/**
* 
* Frete com Correios para Magento
* 
* @category     elOOm
* @package      Modulo Frete com Correios
* @copyright    Copyright (c) 2023 elOOm (https://eloom.tech)
* @version      2.0.0
* @license      https://opensource.org/licenses/OSL-3.0
* @license      https://opensource.org/licenses/AFL-3.0
*
*/
declare(strict_types=1);

namespace Eloom\CorreiosFrete\Model;

use Eloom\Correios\Client;
use Eloom\Correios\Errors;
use Eloom\Correios\Endpoints\Rastro;
use Eloom\Correios\Exceptions\UnauthorizedException;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

class Carrier extends AbstractCarrierOnline implements CarrierInterface {
	
	const CODE = 'eloom_correios';
	const COUNTRY = 'BR';
	
	protected $_code = self::CODE;
	protected $_freeMethod = null;
	protected $_result = null;
	
	private $fromZip = null;
	private $toZip = null;
	private $hasFreeMethod = false;
	private $nVlComprimento = 0;
	private $nVlAltura = 0;
	private $nVlLargura = 0;
	
	private $packageValue;
	
	private $packageWeight;
	
	private $volumeWeight;
	
	private $freeMethodWeight;
	
	private $skipErrors = ['010', '011'];
	
	protected $freeMethodSameCEP = null;
	
	protected $correiosServiceList = [];
	
	private $logger;
	
	public function __construct(ScopeConfigInterface $scopeConfig,
	                            ErrorFactory $rateErrorFactory,
	                            LoggerInterface $logger,
	                            Security $xmlSecurity,
								\Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
								\Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
								\Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
								\Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
								\Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
								\Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
								\Magento\Directory\Model\RegionFactory $regionFactory,
								\Magento\Directory\Model\CountryFactory $countryFactory,
								\Magento\Directory\Model\CurrencyFactory $currencyFactory,
								\Magento\Directory\Helper\Data $directoryData,
								\Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
	                            array $data = [],
	                            \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
	                            \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory) {
		
		parent::__construct($scopeConfig,
							$rateErrorFactory,
							$logger,
							$xmlSecurity,
							$xmlElFactory,
							$rateFactory,
							$rateMethodFactory,
							$trackFactory,
							$trackErrorFactory,
							$trackStatusFactory,
							$regionFactory,
							$countryFactory,
							$currencyFactory,
							$directoryData,
							$stockRegistry,
							$data);
		
		$this->logger = $logger;
	}
	
	private function check(RateRequest $request) {
		if (!$this->getConfigFlag('active')) {
			return false;
		}
		
		$this->_result = $this->_rateResultFactory->create();
		$origCountry = $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_COUNTRY_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $request->getStoreId());
		$destCountry = $request->getDestCountryId();
		if ($origCountry != self::COUNTRY || $destCountry != self::COUNTRY) {
			$rate = $this->_rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setErrorMessage(Errors::getMessage('002'));
			$this->_result->append($rate);
			
			return false;
		}
		$this->fromZip = $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_ZIP, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $request->getStoreId());
		$this->fromZip = str_replace(array('-', '.'), '', trim($this->fromZip));
		
		if (!preg_match('/^([0-9]{8})$/', $this->fromZip)) {
			$rate = $this->_rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setErrorMessage(Errors::getMessage('003'));
			$this->_result->append($rate);
			
			return false;
		}
		$price = 0;
		$weight = 0;
		if ($request->getPackageValue() > 0 && $request->getPackageWeight() > 0) {
			$price = $request->getPackageValue();
			$weight = $request->getPackageWeight();
		} else if ($request->getAllItems()) {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			foreach ($request->getAllItems() as $item) {
				if ($item->getProduct()->isVirtual()) {
					continue;
				}
				
				if ($item->getHasChildren() && $item->isShipSeparately()) {
					foreach ($item->getChildren() as $child) {
						if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
							$product = $objectManager->create(Product::class)->load($child->getProductId());
							
							$price += (float)(!is_null($product->getData('special_price')) ? $product->getData('special_price') : $product->getData('price'));
							$weight += (float)$product->getData('weight');
						}
					}
				} else {
					$product = $objectManager->create(Product::class)->load($item->getProductId());
					if ($product->getTypeId() == 'simple') {
						$price += (float)(!is_null($product->getData('special_price')) ? $product->getData('special_price') : $product->getData('price'));
						$weight += (float)$product->getData('weight');
					}
				}
			}
		}
		$this->nVlAltura = $this->getConfigData('default_height');
		$this->nVlLargura = $this->getConfigData('default_width');
		$this->nVlComprimento = $this->getConfigData('default_length');
		
		$this->hasFreeMethod = $request->getFreeShipping();
		$this->_freeMethod = $this->getConfigData('servico_gratuito');
		
		$this->packageValue = $request->getBaseCurrency()->convert($price, $request->getPackageCurrency());
		$this->packageWeight = number_format(floatval($weight), 2, '.', '');
		$this->freeMethodWeight = number_format(floatval($request->getFreeMethodWeight()), 2, '.', '');
	}
	
	public function collectRates(RateRequest $request) {
		$this->toZip = $request->getDestPostcode();
		if (null == $this->toZip) {
			return $this->_result;
		}
		$this->toZip = str_replace(array('-', '.'), '', trim($this->toZip));
		$this->toZip = str_replace('-', '', $this->toZip);
		if (!preg_match('/^([0-9]{8})$/', $this->toZip)) {
			return $this->_result;
		}
		
		if ($this->check($request) === false) {
			return $this->_result;
		}
		$this->getQuotes();
		
		return $this->_result;
	}
	
	public function getAllowedMethods() {
		$allowedMethods = explode(',', $this->getConfigData('cd_servico'));
		$methods = [];
		foreach ($allowedMethods as $k) {
			$methods[$k] = $this->getCode('service', $k);
		}
		
		return $methods;
	}
	
	public function getCode($type, $code = null) {
		static $codes = [
			'service' => [
				'03085' => '03085 - PAC com contrato',
				'03298' => '03298 - PAC com contrato',
				'04669' => '04669 - PAC com contrato',
				'03050' => '03050 - SEDEX com contrato',
				'03220' => '03220 - SEDEX com contrato',
				'04162' => '04162 - SEDEX com contrato',
				'40126' => '40126 - SEDEX a Cobrar, com contrato'
			],
			'front' => [
				'03085' => 'PAC',
				'03298' => 'PAC',
				'04669' => 'PAC',
				'03050' => 'SEDEX',
				'03220' => 'SEDEX',
				'04162' => 'SEDEX',
				'40126' => 'SEDEX a Cobrar'
			]
		];
		
		if (!isset($codes[$type])) {
			return false;
		} elseif (null === $code) {
			return $codes[$type];
		}
		
		if (!isset($codes[$type][$code])) {
			return false;
		} else {
			return $codes[$type][$code];
		}
	}
	
	protected function getQuotes() {
		$this->calcPrecoPrazo();
		
		if (sizeof($this->correiosServiceList) == 0) {
			$error = $this->_trackErrorFactory->create();
			$error->setCarrier($this->_code);
			$error->setCarrierTitle($this->getConfigData('title'));
			$error->setErrorMessage(Errors::getMessage('001'));
			$this->_result->append($error);
			
			return $this->_result;
		}
		
		foreach ($this->correiosServiceList as $s) {
			$this->appendService($s);
		}
		
		return $this->_result;
	}
	
	private function appendService($servico) {
		$rate = null;
		$method = $servico->coProduto;
		
		if ($servico->txErro != '0' && !in_array($servico->txErro, $this->skipErrors)) {
			if ($this->getConfigData('showmethod')) {
				$title = $this->getCode('front', $method);
				
				$rate = $this->_rateErrorFactory->create();
				$rate->setCarrier($this->_code);
				$rate->setCarrierTitle($this->getConfigData('title'));
				$rate->setErrorMessage(($title != '' ? $title . ' - ' . $servico->txErro : $servico->txErro));
			}
		} else {
			$rate = $this->_rateMethodFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setMethod($method);
			
			$title = $this->getCode('front', $method);
			if ($this->getConfigData('prazo_entrega')) {
				$s = $this->getConfigData('mensagem_prazo_entrega');
				$title = sprintf($s, $title, intval($servico->prazoEntrega + $this->getConfigData('prazo_extra')));
			}
			if ($servico->msgPrazo != '') {
				$title = $title . ' [' . $servico->msgPrazo . ']';
			}
			$title = substr($title, 0, 255);
			$rate->setMethodTitle($title);
			
			$taxaExtra = $this->getConfigData('taxa_extra');
			if ($taxaExtra) {
				$v1 = floatval(str_replace(',', '.', (string) $this->getConfigData('taxa_extra_valor')));
				$v2 = floatval(str_replace(',', '.', (string) $servico->pcFinal));
				
				if ($taxaExtra == '2') {
					$rate->setPrice($v1 + $v2);
				} else if ($taxaExtra == '1') {
					$rate->setPrice($v2 + (($v1 * $v2) / 100));
				}
			} else {
				$rate->setPrice(floatval(str_replace(',', '.', (string) $servico->pcFinal)));
			}
			
			if ($this->hasFreeMethod) {
				if ($method == $this->_freeMethod) {
					$v1 = floatval(str_replace(',', '.', (string)$this->getConfigData('servico_gratuito_desconto')));
					$p = $rate->getPrice();
					if ($v1 > 0 && $v1 > $p) {
						$rate->setPrice(0);
					}
				}
				
				if ($method == $this->freeMethodSameCEP) {
					$rate->setPrice(0);
				}
			}
			
			$rate->setCost(0);
		}
		
		$this->_result->append($rate);
	}
	
	private function calcPrecoPrazo() {
		/**
		 * Autentica
		 */
		$client = new Client($this->getConfigData('usuario'), $this->getConfigData('codigo_acesso'));
		try {
			$client->autentica()->cartaoPostagem($this->getConfigData('cartao_postagem'));
		} catch (UnauthorizedException $exception) {
			$this->logger->critical($exception->getMessage());

			$rate = $this->_rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setErrorMessage(Errors::getMessage('401'));
			$this->_result->append($rate);
			
			return $this->_result;
		}

		$codigoServicos = $this->getConfigData('cd_servico');

		/**
		 * Prazo
		 */
		$prazoClient = $client->prazo();

		if (strpos($codigoServicos, ',')) {
			$l = explode(',', $codigoServicos);
			foreach ($l as $servico) {
				$prazoClient->withProduct($servico);
			}
		} else {
			$prazoClient->withProduct($codigoServicos);
		}
		$prazos = $prazoClient->withCepOrigem($this->fromZip)->withCepDestino($this->toZip)->nacional();

		
		/**
		 * Preço
		 */
		$precoClient = $client->preco();
		if (strpos($codigoServicos, ',')) {
			$l = explode(',', $codigoServicos);
			foreach ($l as $servico) {
				$precoClient->withProduct($servico);
			}
		} else {
			$precoClient->withProduct($codigoServicos);
		}
		$precoClient->withCepOrigem($this->fromZip)->withCepDestino($this->toZip);
		$precoClient->withDiametro(0)->withAltura($this->nVlAltura)->withLargura($this->nVlLargura)->withComprimento($this->nVlComprimento);
		
		$nVlPeso = 0;
		if ($this->volumeWeight > $this->getConfigData('volume_weight_min') && $this->volumeWeight > $this->packageWeight) {
			$nVlPeso = $this->volumeWeight;
		} else {
			$nVlPeso = $this->packageWeight;
		}
		$precoClient->withPsObjeto($nVlPeso);

		if ($this->getConfigData('vl_valor_declarado')) {
			$precoClient->withVlDeclarado($this->packageValue);
		}
		$precoClient->withTpObjeto($this->getConfigData('cd_formato'));

		$precos = $precoClient->nacional();

		/**
		 * Merge
		 */
		foreach($precos as $preco) {
			$this->correiosServiceList[$preco->coProduto] = $preco; 
		}

		foreach($prazos as $prazo) {
			$service = $this->correiosServiceList[$prazo->coProduto];
			$service->prazoEntrega = $prazo->prazoEntrega;
			$service->entregaDomiciliar = $prazo->entregaDomiciliar;
			$service->entregaSabado = $prazo->entregaSabado;
			$service->msgPrazo = $prazo->msgPrazo;

			$this->correiosServiceList[$prazo->coProduto] = $service;
		}
	}
	
	public function isTrackingAvailable() {
		return true;
	}
	
	public function getTrackingInfo($tracking) {
		return $this->searchCorreiosEvents($tracking);
	}
	
	private function searchCorreiosEvents($trackingNumber) {
		/**
		 * Autentica
		 */
		$client = new Client($this->getConfigData('usuario'), $this->getConfigData('codigo_acesso'));
		try {
			$client->autentica()->cartaoPostagem($this->getConfigData('cartao_postagem'));
		} catch (UnauthorizedException $exception) {
			$error = Mage::getModel('shipping/tracking_result_error');
			$error->setTracking($trackingNumber);
			$error->setCarrier($this->_code);
			$error->setCarrierTitle($this->getConfigData('title'));

			$error->setErrorMessage($exception->getMessage());

			$this->_result->append($error);
		}

		try {
			$client->autentica()->cartaoPostagem($this->getConfigData('cartao_postagem'));
		} catch (UnauthorizedException $exception) {
			$this->logger->critical($exception->getMessage());

			$error = $this->_trackErrorFactory->create();
			$error->setTracking($trackingNumber);
			$error->setCarrier($this->_code);
			$error->setCarrierTitle($this->getConfigData('title'));
			$error->setErrorMessage(Errors::getMessage('401'));
			
			return $error;
		}
		
		$objetos = $client->rastro()->withCodigoObjeto($trackingNumber)->withResultado(Rastro::EVENTOS_TODOS)->objeto();
		$objeto = $objetos->objetos[0];
		
		if (isset($objeto->mensagem)) {
			$error = $this->_trackErrorFactory->create();
			$error->setTracking($trackingNumber);
			$error->setCarrier($this->_code);
			$error->setCarrierTitle($this->getConfigData('title'));
			$error->setErrorMessage($objeto->mensagem);
			
			return $error;
		} else {
			$lastEvent = $objeto->eventos[0];
			$endereco = $lastEvent->unidade->endereco;
			
			$track = array(
				'deliverydate' => date('d-m-Y', strtotime($lastEvent->dtHrCriado)),
				'deliverytime' => date('H:i', strtotime($lastEvent->dtHrCriado)),
				'deliverylocation' => (isset($endereco->cidade) ? $endereco->cidade . '&nbsp;/&nbsp;' . $endereco->uf : ''),
				'status' => htmlentities($lastEvent->descricao),
				'progressdetail' => $this->eventsAsString($objeto->eventos),
			);
			
			$tracking = $this->_trackStatusFactory->create();
			$tracking->setTracking($objeto->codObjeto);
			$tracking->setCarrier($this->_code);
			$tracking->setCarrierTitle($this->getConfigData('title'));
			$tracking->addData($track);
			
			return $tracking;
		}
	}
	
	private function eventsAsString($objeto) {
		$detail = array();
		foreach ($objeto->evento as $event) {
			$endereco = $event->unidade->endereco;
			
			$detail[] = array(
				'deliverydate' => date('d-m-Y', strtotime($event->dtHrCriado)),
				'deliverytime' => date('H:i', strtotime($event->dtHrCriado)),
				'deliverylocation' => (isset($endereco->cidade) ? $endereco->cidade . '&nbsp;/&nbsp;' . $endereco->uf : ''),
				'activity' => $event->descricao . (isset($endereco->cidade) ? sprintf(' (Unidade Operacional em %s / %s)', $endereco->cidade, $endereco->uf) : '')
			);
		}
		
		return $detail;
	}
}