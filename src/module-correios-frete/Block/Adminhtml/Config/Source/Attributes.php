<?php
/**
* 
* Frete com Correios para Magento
* 
* @category     elOOm
* @package      Modulo Frete com Correios
* @copyright    Copyright (c) 2023 elOOm (https://eloom.tech)
* @version      2.0.2
* @license      https://opensource.org/licenses/OSL-3.0
* @license      https://opensource.org/licenses/AFL-3.0
*
*/
declare(strict_types=1);

namespace Eloom\CorreiosFrete\Block\Adminhtml\Config\Source;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class Attributes implements \Magento\Framework\Option\ArrayInterface {

	private $attributeFactory;

	public function __construct(Attribute $attributeFactory) {
		$this->attributeFactory = $attributeFactory;
	}

	public function toOptionArray() {
		$attributes = $this->attributeFactory->getCollection();

		$options = [];
		$options[] = ['value' => '', 'label' => 'Selecione'];

		foreach ($attributes as $attribute) {
			$front = $attribute->getFrontendLabel();

			if (!empty($front)) {
				$options[] = ['value' => $attribute->getAttributecode(), 'label' => $attribute->getAttributecode()];
			} else {
				$options[] = ['value' => $attribute->getAttributecode(), 'label' => $attribute->getAttributecode()];
			}
		}

		sort($options);

		return $options;
	}
}