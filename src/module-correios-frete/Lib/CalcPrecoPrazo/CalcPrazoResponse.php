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

class CalcPrazoResponse {

	/**
	 * @var cResultado $CalcPrazoResult
	 * @access public
	 */
	public $CalcPrazoResult = null;

	/**
	 * @param cResultado $CalcPrazoResult
	 * @access public
	 */
	public function __construct($CalcPrazoResult) {
		$this->CalcPrazoResult = $CalcPrazoResult;
	}

}
