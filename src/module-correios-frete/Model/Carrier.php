<?php
/**
* 
* Frete com Correios para Magento 2
* 
* @category     Ã©lOOm
* @package      Modulo Frete com Correios
* @copyright    Copyright (c) 2021 Ã©lOOm (https://www.eloom.com.br)
* @version      1.0.0
* @license      https://opensource.org/licenses/OSL-3.0
* @license      https://opensource.org/licenses/AFL-3.0
*
*/
declare(strict_types=1);

namespace Eloom\CorreiosFrete\Model;

use Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazo;
use Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazoWS;
use Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\Errors;
use Eloom\CorreiosFrete\Lib\Sro\BuscaEventos;
use Eloom\CorreiosFrete\Lib\Sro\Rastro;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Psr\Log\LoggerInterface;

class Carrier extends AbstractCarrier implements CarrierInterface {
	
	const CODE = 'eloom_correios';
	const COUNTRY = 'BR';
	const CODE_SAME_CEP = '-888';
	
	protected $_code = self::CODE;
	protected $_freeMethod = null;
	protected $_result = null;
	
	private $fromZip = null;
	private $toZip = null;
	private $hasFreeMethod = false;
	private $nVlComprimento = 0;
	private $nVlAltura = 0;
	private $nVlLargura = 0;
	
	private $rateErrorFactory;
	
	private $rateResultFactory;
	
	private $rateMethodFactory;
	
	private $trackFactory;
	
	private $trackErrorFactory;
	
	private $trackStatusFactory;
	
	private $packageValue;
	
	private $packageWeight;
	
	private $volumeWeight;
	
	private $freeMethodWeight;
	
	private $hasErrorSameCEP = false;
	
	private $skipErrors = ['010', '011'];
	
	protected $freeMethodSameCEP = null;
	
	protected $reverse = ['3050' => '03050', '3085' => '03085', '4510' => '04510', '4669' => '04669', '4014' => '04014', '4162' => '04162'];
	
	protected $correiosServiceList = [];
	
	private $logger;
	
	public function __construct(ScopeConfigInterface $scopeConfig,
	                            ErrorFactory $rateErrorFactory,
	                            LoggerInterface $logger,
	                            ResultFactory $rateResultFactory,
	                            MethodFactory $rateMethodFactory,
	                            array $data = [],
	                            \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
	                            \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
	                            \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory) {
		parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
		
		$this->rateErrorFactory = $rateErrorFactory;
		$this->rateResultFactory = $rateResultFactory;
		$this->rateMethodFactory = $rateMethodFactory;
		$this->trackFactory = $trackFactory;
		$this->trackErrorFactory = $trackErrorFactory;
		$this->trackStatusFactory = $trackStatusFactory;
		$this->logger = $logger;
	}
	
	private function check(RateRequest $request) {
		if (!$this->getConfigFlag('active')) {
			return false;
		}
		
		$this->_result = $this->rateResultFactory->create();
		$origCountry = $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_COUNTRY_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $request->getStoreId());
		$destCountry = $request->getDestCountryId();
		if ($origCountry != self::COUNTRY || $destCountry != self::COUNTRY) {
			$rate = $this->rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setErrorMessage(Errors::getMessage('002'));
			$this->_result->append($rate);
			
			return false;
		}
		$this->fromZip = $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_ZIP, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $request->getStoreId());
		$this->fromZip = str_replace(array('-', '.'), '', trim($this->fromZip));
		
		if (!preg_match('/^([0-9]{8})$/', $this->fromZip)) {
			$rate = $this->rateErrorFactory->create();
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
					}
					
					if ($product->getTypeId() == 'simple') {
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
				'04510' => '04510 - PAC sem contrato',
				'04669' => '04669 - PAC com contrato',
				'03050' => '03050 - SEDEX com contrato',
				'04014' => '04014 - SEDEX sem contrato',
				'04162' => '04162 - SEDEX com contrato',
				'40045' => '40045 - SEDEX a Cobrar, sem contrato',
				'40126' => '40126 - SEDEX a Cobrar, com contrato',
				'40215' => '40215 - SEDEX 10, sem contrato',
				'40290' => '40290 - SEDEX Hoje, sem contrato'
			],
			'front' => [
				'03085' => 'PAC',
				'04510' => 'PAC',
				'04669' => 'PAC',
				'03050' => 'SEDEX',
				'04014' => 'SEDEX',
				'04162' => 'SEDEX',
				'40045' => 'SEDEX a Cobrar',
				'40126' => 'SEDEX a Cobrar',
				'40215' => 'SEDEX 10',
				'40290' => 'SEDEX Hoje'
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
			$rate = $this->rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setErrorMessage(Errors::getMessage('001'));
			$this->_result->append($rate);
			
			return $this->_result;
		}
		$this->checkErrorSameCep();
		
		foreach ($this->correiosServiceList as $s) {
			$this->appendService($s);
		}
		
		return $this->_result;
	}
	
	private function checkErrorSameCep() {
		foreach ($this->correiosServiceList as $servico) {
			if ($servico->Erro == self::CODE_SAME_CEP) {
				$this->hasErrorSameCEP = true;
			}
		}
		
		if ($this->hasErrorSameCEP) {
			$cheapValue = null;
			foreach ($this->correiosServiceList as $servico) {
				if ($servico->Erro == '0') {
					$v = floatval(str_replace(',', '.', (string)$servico->Valor));
					if ($cheapValue == null || $v < $cheapValue) {
						$cheapValue = $v;
						$this->freeMethodSameCEP = $this->checkReverse($servico->Codigo);
					}
				}
			}
		}
	}
	
	private function appendService($servico) {
		$rate = null;
		$method = $this->checkReverse($servico->Codigo);
		
		if ($servico->Erro != '0' && !in_array($servico->Erro, $this->skipErrors)) {
			if ($this->getConfigData('showmethod')) {
				$title = $this->getCode('front', $method);
				
				$rate = $this->rateErrorFactory->create();
				$rate->setCarrier($this->_code);
				$rate->setCarrierTitle($this->getConfigData('title'));
				$rate->setErrorMessage(($title != '' ? $title . ' - ' . $servico->MsgErro : $servico->MsgErro));
			}
		} else {
			$rate = $this->rateMethodFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setMethod($method);
			
			$title = $this->getCode('front', $method);
			if ($this->getConfigData('prazo_entrega')) {
				$s = $this->getConfigData('mensagem_prazo_entrega');
				$title = sprintf($s, $title, intval($servico->PrazoEntrega + $this->getConfigData('prazo_extra')));
			}
			if ($servico->obsFim != '') {
				$title = $title . ' [' . $servico->obsFim . ']';
			}
			$title = substr($title, 0, 255);
			$rate->setMethodTitle($title);
			
			$taxaExtra = $this->getConfigData('taxa_extra');
			if ($taxaExtra) {
				$v1 = floatval(str_replace(',', '.', (string)$this->getConfigData('taxa_extra_valor')));
				$v2 = floatval(str_replace(',', '.', (string)$servico->Valor));
				
				if ($taxaExtra == '2') {
					$rate->setPrice($v1 + $v2);
				} else if ($taxaExtra == '1') {
					$rate->setPrice($v2 + (($v1 * $v2) / 100));
				}
			} else {
				$rate->setPrice(floatval(str_replace(',', '.', (string)$servico->Valor)));
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
		$calcPrecoPrazo = new CalcPrecoPrazo();
		$calcPrecoPrazo->sCepOrigem = $this->fromZip;
		$calcPrecoPrazo->sCepDestino = $this->toZip;
		
		if ($this->volumeWeight > $this->getConfigData('volume_weight_min') && $this->volumeWeight > $this->packageWeight) {
			$calcPrecoPrazo->nVlPeso = $this->volumeWeight;
		} else {
			$calcPrecoPrazo->nVlPeso = $this->packageWeight;
		}
		if ($this->getConfigData('vl_valor_declarado')) {
			$calcPrecoPrazo->nVlValorDeclarado = $this->packageValue;
		} else {
			$calcPrecoPrazo->nVlValorDeclarado = 0;
		}
		$calcPrecoPrazo->nVlComprimento = $this->nVlComprimento;
		$calcPrecoPrazo->nVlAltura = $this->nVlAltura;
		$calcPrecoPrazo->nVlLargura = $this->nVlLargura;
		$calcPrecoPrazo->nVlDiametro = 0;
		
		$calcPrecoPrazo->nCdEmpresa = (null == $this->getConfigData('cd_empresa') ? '' : trim($this->getConfigData('cd_empresa')));
		$calcPrecoPrazo->sDsSenha = (null == $this->getConfigData('ds_senha') ? '' : trim($this->getConfigData('ds_senha')));
		$calcPrecoPrazo->nCdFormato = $this->getConfigData('cd_formato');
		
		if ($this->getConfigData('cd_aviso_recebimento')) {
			$calcPrecoPrazo->sCdAvisoRecebimento = 'S';
		} else {
			$calcPrecoPrazo->sCdAvisoRecebimento = 'N';
		}
		if ($this->getConfigData('cd_mao_propria')) {
			$calcPrecoPrazo->sCdMaoPropria = 'S';
		} else {
			$calcPrecoPrazo->sCdMaoPropria = 'N';
		}
		
		try {
			$calculaFreteResponse = null;
			$calcPrecoPrazoWS = new CalcPrecoPrazoWS();
			$calcPrecoPrazoWS->__setLocation('http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx');
			
			if (null == $calcPrecoPrazo->nCdEmpresa) {
				$cdServico = $this->getConfigData('cd_servico');
				if (strpos($cdServico, ',')) {
					$l = explode(',', $cdServico);
					foreach ($l as $s) {
						$calcPrecoPrazo->nCdServico = $s;
						$calculaFreteResponse = $calcPrecoPrazoWS->CalcPrecoPrazo($calcPrecoPrazo);
						$this->addCorreiosService($calculaFreteResponse->CalcPrecoPrazoResult->Servicos);
					}
				} else {
					$calcPrecoPrazo->nCdServico = $cdServico;
					$calculaFreteResponse = $calcPrecoPrazoWS->CalcPrecoPrazo($calcPrecoPrazo);
					$this->addCorreiosService($calculaFreteResponse->CalcPrecoPrazoResult->Servicos);
				}
			} else {
				$calcPrecoPrazo->nCdServico = $this->getConfigData('cd_servico');
				$calculaFreteResponse = $calcPrecoPrazoWS->CalcPrecoPrazo($calcPrecoPrazo);
				
				$this->addCorreiosService($calculaFreteResponse->CalcPrecoPrazoResult->Servicos);
			}
		} catch (SoapFault $sf) {
			$this->logger->critical($sf->getMessage());
		}
	}
	
	private function addCorreiosService($servico) {
		if (is_array($servico->cServico)) {
			foreach ($servico->cServico as $s) {
				$this->correiosServiceList[] = $s;
			}
		} else {
			$this->correiosServiceList[] = $servico->cServico;
		}
	}
	
	private function checkReverse($code) {
		if (isset($this->reverse[$code])) {
			return $this->reverse[$code];
		}
		
		return $code;
	}
	
	public function isTrackingAvailable() {
		$user = $this->getConfigData('sro_user');
		$pwd = $this->getConfigData('sro_password');
		
		return (null != $user && null != $pwd);
	}
	
	public function getTrackingInfo($tracking) {
		return $this->searchCorreiosEvents($tracking);
	}
	
	private function searchCorreiosEvents($trackingNumber) {
		$user = $this->getConfigData('sro_user');
		$pwd = $this->getConfigData('sro_password');
		if (empty($user) || empty($pwd)) {
			$error = $this->trackErrorFactory->create();
			$error->setTracking($trackingNumber);
			$error->setCarrier($this->_code);
			$error->setCarrierTitle($this->getConfigData('title'));
			$error->setErrorMessage('É necessário informar o Usuário e senha do Sistema de Rastreamento de Objetos.');
			
			return $error;
		}
		
		$buscaEventos = new BuscaEventos($user, $pwd, 'L', 'L', '101', $trackingNumber);
		$rastroWs = new Rastro();
		$buscaEventosResponse = $rastroWs->buscaEventos($buscaEventos);
		
		$objeto = $buscaEventosResponse->return->objeto;
		$rate = null;
		
		if ($objeto == null || $objeto == '' || isset($objeto->erro)) {
			$error = $this->trackErrorFactory->create();
			$error->setTracking($trackingNumber);
			$error->setCarrier($this->_code);
			$error->setCarrierTitle($this->getConfigData('title'));
			$error->setErrorMessage($objeto->erro);
			
			return $error;
		} else {
			$lastEvent = $objeto->evento[0];
			$dataEntrega = str_replace('/', '-', $lastEvent->data);
			
			$track = array(
				'deliverydate' => date('d-m-Y', strtotime($dataEntrega)),
				'deliverytime' => date('H:i', strtotime($lastEvent->hora)),
				'deliverylocation' => $lastEvent->cidade . '&nbsp;/&nbsp;' . $lastEvent->uf,
				'status' => htmlentities($lastEvent->descricao),
				'progressdetail' => $this->eventsAsString($objeto),
			);
			
			$tracking = $this->trackStatusFactory->create();
			$tracking->setTracking($trackingNumber);
			$tracking->setCarrier($this->_code);
			$tracking->setCarrierTitle($this->getConfigData('title'));
			$tracking->addData($track);
			
			return $tracking;
		}
	}
	
	private function eventsAsString($objeto) {
		$detail = array();
		foreach ($objeto->evento as $event) {
			$dataEntrega = str_replace('/', '-', $event->data);
			$destino = (isset($event->destino) ? $event->destino : null);
			
			$detail[] = array(
				'deliverydate' => date('d-m-Y', strtotime($dataEntrega)),
				'deliverytime' => $event->hora,
				'deliverylocation' => $event->cidade . '&nbsp;/&nbsp;' . $event->uf,
				'activity' => $event->descricao . (!is_null($destino) ? sprintf(' (Unidade Operacional em %s / %s)', $destino->cidade, $destino->uf) : ''),
			);
		}
		
		return $detail;
	}
}