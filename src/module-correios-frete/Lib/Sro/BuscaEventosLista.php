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

class BuscaEventosLista {

	public $usuario = null;

	public $senha = null;

	public $tipo = null;

	public $resultado = null;

	public $lingua = null;

	public $objetos = null;

	public function __construct($usuario, $senha, $tipo, $resultado, $lingua, $objetos) {
		$this->usuario = $usuario;
		$this->senha = $senha;
		$this->tipo = $tipo;
		$this->resultado = $resultado;
		$this->lingua = $lingua;
		$this->objetos = $objetos;
	}

}
