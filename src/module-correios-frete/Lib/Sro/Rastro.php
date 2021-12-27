<?php
/**
* 
* Frete com Correios para Magento
* 
* @category     elOOm
* @package      Modulo Frete com Correios
* @copyright    Copyright (c) 2021 elOOm (https://eloom.tech)
* @version      1.0.1
* @license      https://opensource.org/licenses/OSL-3.0
* @license      https://opensource.org/licenses/AFL-3.0
*
*/
declare(strict_types=1);

namespace Eloom\CorreiosFrete\Lib\Sro;

class Rastro extends \SoapClient {

	/**
	 * @var array $classmap The defined classes
	 * @access private
	 */
	private static $classmap = array(
		'buscaEventosLista' => '\Eloom\CorreiosFrete\Lib\Sro\BuscaEventosLista',
		'buscaEventosListaResponse' => '\Eloom\CorreiosFrete\Lib\Sro\BuscaEventosListaResponse',
		'sroxml' => '\Eloom\CorreiosFrete\Lib\Sro\Sroxml',
		'objeto' => '\Eloom\CorreiosFrete\Lib\Sro\Objeto',
		'eventos' => '\Eloom\CorreiosFrete\Lib\Sro\Eventos',
		'destinos' => '\Eloom\CorreiosFrete\Lib\Sro\Destinos',
		'enderecoMobile' => '\Eloom\CorreiosFrete\Lib\Sro\EnderecoMobile',
		'buscaEventos' => '\Eloom\CorreiosFrete\Lib\Sro\BuscaEventos',
		'buscaEventosResponse' => '\Eloom\CorreiosFrete\Lib\Sro\BuscaEventosResponse');

	/**
	 * @param array $options A array of config values
	 * @param string $wsdl The wsdl file to use
	 * @access public
	 */
	public function __construct(array $options = array(), $wsdl = 'http://webservice.correios.com.br/service/rastro/Rastro.wsdl') {
		foreach (self::$classmap as $key => $value) {
			if (!isset($options['classmap'][$key])) {
				$options['classmap'][$key] = $value;
			}
		}

		parent::__construct($wsdl, $options);
	}

	/**
	 * @param BuscaEventos $parameters
	 * @access public
	 * @return BuscaEventosResponse
	 */
	public function buscaEventos(BuscaEventos $parameters) {
		return $this->__soapCall('buscaEventos', array($parameters));
	}

	/**
	 * @param BuscaEventosLista $parameters
	 * @access public
	 * @return BuscaEventosListaResponse
	 */
	public function buscaEventosLista(BuscaEventosLista $parameters) {
		return $this->__soapCall('buscaEventosLista', array($parameters));
	}

}
