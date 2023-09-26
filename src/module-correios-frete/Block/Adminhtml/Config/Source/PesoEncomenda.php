<?php
/**
* 
* Frete com Correios para Magento
* 
* @category     elOOm
* @package      Modulo Frete com Correios
* @copyright    Copyright (c) 2023 elOOm (https://eloom.tech)
* @version      2.0.1
* @license      https://opensource.org/licenses/OSL-3.0
* @license      https://opensource.org/licenses/AFL-3.0
*
*/
declare(strict_types=1);

namespace Eloom\CorreiosFrete\Block\Adminhtml\Config\Source;

class PesoEncomenda implements \Magento\Framework\Option\ArrayInterface {

	const WEIGHT_GR = 'gr';
	const WEIGHT_KG = 'kg';

	public function toOptionArray() {
		return [
			['value' => self::WEIGHT_GR, 'label' => __('Gramas')],
			['value' => self::WEIGHT_KG, 'label' => __('Kilos')],
		];
	}
}