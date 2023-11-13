<?php
/**
* 
* Frete com Correios para Magento
* 
* @category     elOOm
* @package      Modulo Frete com Correios
* @copyright    Copyright (c) 2023 elOOm (https://eloom.tech)
* @version      2.0.2
* @license      https://opensource.org/licenses/OSL-3.0
* @license      https://opensource.org/licenses/AFL-3.0
*
*/
declare(strict_types=1);

namespace Eloom\CorreiosFrete\Model;

use Eloom\SdkCorreios\Client;
use Eloom\SdkCorreios\Errors;
use Eloom\SdkCorreios\Endpoints\Rastro;
use Eloom\SdkCorreios\Exceptions\UnauthorizedException;
use Eloom\SdkCorreios\Exceptions\CorreiosException;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Psr\Log\LoggerInterface;

class Carrier extends AbstractCarrierOnline implements CarrierInterface {
	
	const CODE = 'eloom_correios';
	const COUNTRY = 'BR';
	
	protected $_code = self::CODE;
	protected $_freeMethod = null;
	
	private $fromZip = null;
	private $toZip = null;
	private $hasFreeMethod = false;
	private $totalLength = 0;
	private $totalHeight = 0;
	private $totalWidth = 0;
	
	/**
     * Rate result data
     *
     * @var Result|null
     */
    protected $_result = null;

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
	                            \Magento\Framework\Xml\Security $xmlSecurity,
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
	                            array $data = []) {
		
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

	/**
     * Processing additional validation to check if carrier applicable.
     *
     * @param \Magento\Framework\DataObject $request
     * @return $this|bool|\Magento\Framework\DataObject
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @since 100.2.6
     */
    public function processAdditionalValidation(\Magento\Framework\DataObject $request) {
		return $this;
	}
	
	private function check(RateRequest $request) {
		if (!$this->getConfigFlag('active')) {
			return false;
		}
		
		$origCountry = $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_COUNTRY_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $request->getStoreId());
		$destCountry = $request->getDestCountryId();
		if ($origCountry != self::COUNTRY || $destCountry != self::COUNTRY) {
			$rate = $this->_rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setErrorMessage(Errors::getMessage('002'));
			$this->getRateResult()->append($rate);
			
			return false;
		}
		$this->fromZip = $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_ZIP, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $request->getStoreId());
		$this->fromZip = preg_replace('/\D/', '', $this->fromZip);

		if (!preg_match('/^([0-9]{8})$/', $this->fromZip)) {
			$rate = $this->_rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setErrorMessage(Errors::getMessage('003'));
			$this->getRateResult()->append($rate);
			
			return false;
		}
		$weightAttr = $this->getConfigData('weight');
		
		$widthAttr = $this->getConfigData('width');
		$heightAttr = $this->getConfigData('height');
		$lengthAttr = $this->getConfigData('length');

		$defaultHeight = $this->getConfigData('default_height');
		$defaultWidth = $this->getConfigData('default_width');
		$defaultLength = $this->getConfigData('default_length');

		$price = 0;
		$weight = 0;

		$width = 0;
		$height = 0;
		$length = 0;

		if ($request->getAllItems()) {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			foreach ($request->getAllItems() as $item) {
				if ($item->getProduct()->isVirtual()) {
					continue;
				}
				
				if ($item->getHasChildren()) {
					foreach ($item->getChildren() as $child) {
						if (!$child->getProduct()->isVirtual()) {
							$product = $objectManager->create('Magento\Catalog\Model\Product')->load($child->getProductId());

							$price += ($item->getPrice() - $item->getDiscountAmount());
							$parentIds = $objectManager->create('Magento\GroupedProduct\Model\Product\Type\Grouped')->getParentIdsByChild($product->getId());
							if (!$parentIds) {
								$parentIds = $objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->getParentIdsByChild($product->getId());

								if ($parentIds) {
									$parentProd = $objectManager->create('Magento\Catalog\Model\Product')->load($parentIds[0]);
						
									$weight += (float)$parentProd->getData($weightAttr);
									$width += ($parentProd->getData($widthAttr) > 0 ? $parentProd->getData($widthAttr) : $defaultWidth);
									$height += ($parentProd->getData($heightAttr) > 0 ? $parentProd->getData($heightAttr) : $defaultHeight);
									$length += ($parentProd->getData($lengthAttr) > 0 ? $parentProd->getData($lengthAttr) : $defaultLength);
								}
							}
						}
					}
				} else {
					$product = $objectManager->create(Product::class)->load($item->getProductId());
					if ($product->getTypeId() == 'simple') {
						$price += (float)(!is_null($product->getData('special_price')) ? $product->getData('special_price') : $product->getData('price'));
						
						$weight += (float)$product->getData($weightAttr);
						$width += ($product->getData($widthAttr) > 0 ? $product->getData($widthAttr) : $defaultWidth);
						$height += ($product->getData($heightAttr) > 0 ? $product->getData($heightAttr) : $defaultHeight);
						$length += ($product->getData($lengthAttr) > 0 ? $product->getData($lengthAttr) : $defaultLength);
					}
				}
			}
		}
		$this->totalHeight = $height;
		$this->totalWidth = $width;
		$this->totalLength = $length;
		
		$this->hasFreeMethod = $request->getFreeShipping();
		$this->_freeMethod = $this->getConfigData('servico_gratuito');

		$this->packageValue = $request->getBaseCurrency()->convert($price, $request->getPackageCurrency());
		$this->packageWeight = number_format(floatval($weight), 2, '.', '');
		$this->freeMethodWeight = number_format(floatval($request->getFreeMethodWeight()), 2, '.', '');
	}
	
	public function collectRates(RateRequest $request) {
		$this->toZip = $request->getDestPostcode();
		if (null == $this->toZip) {
			return $this->getRateResult();
		}

		$this->toZip = preg_replace('/\D/', '', $this->toZip);
		if (!preg_match('/^([0-9]{8})$/', $this->toZip)) {
			return $this->getRateResult();
		}
		
		if ($this->check($request) === false) {
			return $this->getRateResult();
		}
		$this->getQuotes();
		
		return $this->getRateResult();
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
				'03050' => '03050 - SEDEX com contrato',
				'03085' => '03085 - PAC com contrato',
				'03140' => '03140 - SEDEX 12 com contrato',
				'03158' => '03158 - SEDEX 10 com contrato',
				'03220' => '03220 - SEDEX com contrato',
				'03298' => '03298 - PAC com contrato',
				'04162' => '04162 - SEDEX com contrato',
				'04227' => '04227 - PAC Mini, com contrato',
				'04669' => '04669 - PAC com contrato',
				'40126' => '40126 - SEDEX a Cobrar, com contrato'
			],
			'front' => [
				'03050' => 'SEDEX',
				'03085' => 'PAC',
				'03140' => 'SEDEX 12',
				'03158' => 'SEDEX 10',
				'03220' => 'SEDEX',
				'03298' => 'PAC',
				'04162' => 'SEDEX',
				'04227' => 'PAC Mini',
				'04669' => 'PAC',
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
			$this->getRateResult()->append($error);
			
			return $this->getRateResult();
		}
		
		foreach ($this->correiosServiceList as $s) {
			$this->appendService($s);
		}
		
		return $this->getRateResult();
	}
	
	private function appendService($servico) {
		$rate = null;
		$method = $servico->coProduto;
		
		if (isset($servico->txErro) && !in_array($servico->txErro, $this->skipErrors)) {
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
			if (isset($servico->msgPrazo)) {
				$title = $title . ' [' . $servico->msgPrazo . ']';
			}
			$title = substr($title, 0, 250);
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
		
		$this->getRateResult()->append($rate);
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
			$this->getRateResult()->append($rate);
			
			return $this->getRateResult();
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
		$precoClient->withDiametro(0)->withAltura($this->totalHeight)->withLargura($this->totalWidth)->withComprimento($this->totalLength);
		
		$nVlPeso = 0;
		if ($this->volumeWeight > $this->getConfigData('volume_weight_min') && $this->volumeWeight > $this->packageWeight) {
			$nVlPeso = $this->volumeWeight;
		} else {
			$nVlPeso = $this->packageWeight;
		}

		if ($this->getConfigData('tp_vl_peso') == 'kg') {
			$nVlPeso = $nVlPeso * 1000;
		}

		$precoClient->withPsObjeto($nVlPeso);

		if ($this->getConfigData('vl_valor_declarado')) {
			$precoClient->withVlDeclarado($this->packageValue);
		}
		$precoClient->withTpObjeto($this->getConfigData('cd_formato'));

		try {
			$precos = $precoClient->nacional();
		} catch (CorreiosException $exception) {
			$this->logger->critical($exception->getMessage());

			$rate = $this->_rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setErrorMessage($exception->getMessage());
			$this->getRateResult()->append($rate);
			
			return $this->getRateResult();
		}

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
			if(isset($prazo->msgPrazo)) {
				$service->msgPrazo = $prazo->msgPrazo;
			}

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

			$this->getTrackingResult()->append($error);
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
	
	private function eventsAsString($eventos) {
		$detail = array();
		foreach ($eventos as $event) {
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

	/**
     * Do shipment request to carrier web service, obtain Print Shipping Labels and process errors in response
     *
     * @param \Magento\Framework\DataObject $request
     * @return \Magento\Framework\DataObject
     */
    protected function _doShipmentRequest(\Magento\Framework\DataObject $request) {
		$result = new \Magento\Framework\DataObject();

		return $result;
	}

	/**
     * Get result of request
     *
     * @return Result|null
     */
    public function getTrackingResult() {
        if (!$this->_result) {
            $this->_result = $this->_trackFactory->create();
        }

        return $this->_result;
    }

	/**
     * Get result of request
     *
     * @return Result|null
     */
    public function getRateResult() {
        if (!$this->_result) {
            $this->_result = $this->_rateFactory->create();
        }

        return $this->_result;
    }
}