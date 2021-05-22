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
