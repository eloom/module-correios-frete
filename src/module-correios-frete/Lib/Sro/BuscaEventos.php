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

namespace Eloom\CorreiosFrete\Lib\Sro;

class BuscaEventos {

	public $usuario = null;

	public $senha = null;

	public $tipo = null;

	public $resultado = null;

	public $lingua = null;

	public $objetos = null;

	/**
	 * BuscaEventos constructor.
	 * @param $usuario
	 * @param $senha
	 * @param $tipo
	 * @param $resultado
	 * @param $lingua
	 * @param $objetos
	 */
	public function __construct($usuario, $senha, $tipo, $resultado, $lingua, $objetos) {
		$this->usuario = $usuario;
		$this->senha = $senha;
		$this->tipo = $tipo;
		$this->resultado = $resultado;
		$this->lingua = $lingua;
		$this->objetos = $objetos;
	}
}