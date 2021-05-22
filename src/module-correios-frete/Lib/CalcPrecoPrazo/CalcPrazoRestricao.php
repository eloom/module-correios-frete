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

namespace Eloom\CorreiosFrete\Lib\CalcPrecoPrazo;

class CalcPrazoRestricao {

	/**
	 * @var string $nCdServico
	 * @access public
	 */
	public $nCdServico = null;

	/**
	 * @var string $sCepOrigem
	 * @access public
	 */
	public $sCepOrigem = null;

	/**
	 * @var string $sCepDestino
	 * @access public
	 */
	public $sCepDestino = null;

	/**
	 * @var string $sDtCalculo
	 * @access public
	 */
	public $sDtCalculo = null;

	/**
	 * @param string $nCdServico
	 * @param string $sCepOrigem
	 * @param string $sCepDestino
	 * @param string $sDtCalculo
	 * @access public
	 */
	public function __construct($nCdServico, $sCepOrigem, $sCepDestino, $sDtCalculo) {
		$this->nCdServico = $nCdServico;
		$this->sCepOrigem = $sCepOrigem;
		$this->sCepDestino = $sCepDestino;
		$this->sDtCalculo = $sDtCalculo;
	}

}
