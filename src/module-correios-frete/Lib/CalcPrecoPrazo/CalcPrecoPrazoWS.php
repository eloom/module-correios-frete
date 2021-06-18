<?php
/**
* 
* Frete com Correios para Magento 2
* 
* @category     Ã©lOOm
* @package      Modulo Frete com Correios
* @copyright    Copyright (c) 2021 Ã©lOOm (https://eloom.tech)
* @version      1.0.0
* @license      https://opensource.org/licenses/OSL-3.0
* @license      https://opensource.org/licenses/AFL-3.0
*
*/
declare(strict_types=1);

namespace Eloom\CorreiosFrete\Lib\CalcPrecoPrazo;

class CalcPrecoPrazoWS extends \SoapClient {

	const TIMEOUT = '30';

	private static $classmap = array(
		'CalcPrecoPrazo' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazo',
		'CalcPrecoPrazoResponse' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazoResponse',
		'Resultado' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\Resultado',
		'Servico' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\Servico',
		'CalcPrecoPrazoData' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazoData',
		'CalcPrecoPrazoDataResponse' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazoDataResponse',
		'CalcPrecoPrazoRestricao' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazoRestricao',
		'CalcPrecoPrazoRestricaoResponse' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazoRestricaoResponse',
		'CalcPreco' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPreco',
		'CalcPrecoResponse' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoResponse',
		'CalcPrecoData' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoData',
		'CalcPrecoDataResponse' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoDataResponse',
		'CalcPrazo' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrazo',
		'CalcPrazoResponse' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrazoResponse',
		'CalcPrazoData' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrazoData',
		'CalcPrazoDataResponse' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrazoDataResponse',
		'CalcPrazoRestricao' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrazoRestricao',
		'CalcPrazoRestricaoResponse' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrazoRestricaoResponse',
		'CalcPrecoFAC' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoFAC',
		'CalcPrecoFACResponse' => '\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoFACResponse');

	public function __construct(array $options = array(), $wsdl = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx?wsdl') {
		foreach (self::$classmap as $key => $value) {
			if (!isset($options['classmap'][$key])) {
				$options['classmap'][$key] = $value;
			}
		}
		ini_set('default_socket_timeout', self::TIMEOUT);
		parent::__construct($wsdl, $options);
	}

	public function CalcPrecoPrazo(\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazo $parameters) {
		return $this->__soapCall('CalcPrecoPrazo', array($parameters));
	}

	public function CalcPrecoPrazoData(\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazoData $parameters) {
		return $this->__soapCall('CalcPrecoPrazoData', array($parameters));
	}

	public function CalcPrecoPrazoRestricao(\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazoRestricao $parameters) {
		return $this->__soapCall('CalcPrecoPrazoRestricao', array($parameters));
	}

	public function CalcPreco(\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPreco $parameters) {
		return $this->__soapCall('CalcPreco', array($parameters));
	}

	public function CalcPrecoData(\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoData $parameters) {
		return $this->__soapCall('CalcPrecoData', array($parameters));
	}

	public function CalcPrazo(\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrazo $parameters) {
		return $this->__soapCall('CalcPrazo', array($parameters));
	}

	public function CalcPrazoData(\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrazoData $parameters) {
		return $this->__soapCall('CalcPrazoData', array($parameters));
	}

	public function CalcPrazoRestricao(\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrazoRestricao $parameters) {
		return $this->__soapCall('CalcPrazoRestricao', array($parameters));
	}

	public function CalcPrecoFAC(\Eloom\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoFAC $parameters) {
		return $this->__soapCall('CalcPrecoFAC', array($parameters));
	}

}
