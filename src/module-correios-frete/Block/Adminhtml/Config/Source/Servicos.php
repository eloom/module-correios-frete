<?php
/**
* 
* Frete com Correios para Magento
* 
* @category     elOOm
* @package      Modulo Frete com Correios
* @copyright    Copyright (c) 2023 elOOm (https://eloom.tech)
* @version      2.0.0
* @license      https://opensource.org/licenses/OSL-3.0
* @license      https://opensource.org/licenses/AFL-3.0
*
*/
declare(strict_types=1);

namespace Eloom\CorreiosFrete\Block\Adminhtml\Config\Source;

use Eloom\CorreiosFrete\Model\Carrier;

class Servicos implements \Magento\Framework\Option\ArrayInterface {

	private $carrier;

	public function __construct(Carrier $carrier) {
		$this->carrier = $carrier;
	}

	/**
	 * {@inheritdoc}
	 */
	public function toOptionArray() {
		$options = [];
		foreach ($this->carrier->getCode('service') as $k => $v) {
			$options[] = ['value' => $k, 'label' => $v];
		}

		return $options;
	}
}