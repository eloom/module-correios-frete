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

namespace Eloom\CorreiosFrete\Lib\Sro;

class Sroxml {

	/**
	 * @var string $versao
	 * @access public
	 */
	public $versao = null;

	/**
	 * @var string $qtd
	 * @access public
	 */
	public $qtd = null;

	/**
	 * @var string $TipoPesquisa
	 * @access public
	 */
	public $TipoPesquisa = null;

	/**
	 * @var string $TipoResultado
	 * @access public
	 */
	public $TipoResultado = null;

	/**
	 * @var Objeto $objeto
	 * @access public
	 */
	public $objeto = null;

	/**
	 * @param string $versao
	 * @param string $qtd
	 * @param string $TipoPesquisa
	 * @param string $TipoResultado
	 * @param Objeto $objeto
	 * @access public
	 */
	public function __construct($versao, $qtd, $TipoPesquisa, $TipoResultado, $objeto) {
		$this->versao = $versao;
		$this->qtd = $qtd;
		$this->TipoPesquisa = $TipoPesquisa;
		$this->TipoResultado = $TipoResultado;
		$this->objeto = $objeto;
	}
}
