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

namespace Eloom\CorreiosFrete\Lib\CalcPrecoPrazo;

class CalcPrazoData {

	public $nCdServico = null;

	public $sCepOrigem = null;

	public $sCepDestino = null;

	public $sDtCalculo = null;

	public function __construct($nCdServico, $sCepOrigem, $sCepDestino, $sDtCalculo) {
		$this->nCdServico = $nCdServico;
		$this->sCepOrigem = $sCepOrigem;
		$this->sCepDestino = $sCepDestino;
		$this->sDtCalculo = $sDtCalculo;
	}
}