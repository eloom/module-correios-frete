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

class Objeto {

	/**
	 * @var string $numero
	 * @access public
	 */
	public $numero = null;

	/**
	 * @var string $sigla
	 * @access public
	 */
	public $sigla = null;

	/**
	 * @var string $nome
	 * @access public
	 */
	public $nome = null;

	/**
	 * @var string $categoria
	 * @access public
	 */
	public $categoria = null;

	/**
	 * @var string $erro
	 * @access public
	 */
	public $erro = null;

	/**
	 * @var Eventos $evento
	 * @access public
	 */
	public $evento = null;

	/**
	 * @param string $numero
	 * @param string $sigla
	 * @param string $nome
	 * @param string $categoria
	 * @param string $erro
	 * @param Eventos $evento
	 * @access public
	 */
	public function __construct($numero, $sigla, $nome, $categoria, $erro, $evento) {
		$this->numero = $numero;
		$this->sigla = $sigla;
		$this->nome = $nome;
		$this->categoria = $categoria;
		$this->erro = $erro;
		$this->evento = $evento;
	}
}
