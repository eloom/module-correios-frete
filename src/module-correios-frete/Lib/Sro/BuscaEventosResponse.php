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

class BuscaEventosResponse {

	/**
	 * @var Sroxml $return
	 * @access public
	 */
	public $return = null;

	/**
	 * @param Sroxml $return
	 * @access public
	 */
	public function __construct(Sroxml $return) {
		$this->return = $return;
	}
}
