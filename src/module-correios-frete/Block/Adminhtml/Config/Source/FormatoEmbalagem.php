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

namespace Eloom\CorreiosFrete\Block\Adminhtml\Config\Source;

class FormatoEmbalagem implements \Magento\Framework\Option\ArrayInterface {

	public function toOptionArray() {
		return [
			['value' => '1', 'label' => __('Caixa/Pacote')],
			['value' => '2', 'label' => __('Rolo/Prisma')],
			['value' => '3', 'label' => __('Envelope')]
		];
	}
}