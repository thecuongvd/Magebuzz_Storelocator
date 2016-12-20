<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Model\Config\Source;

class PageType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => '', 'value' => '--Select Type--'],
            ['label' => '1 column', 'value' => '1'],
            ['label' => '2 columns with map on the right', 'value' => '2']
        ];
    }

    public function toArray()
    {
        return [
            '' => '--Select Type--',
            '1' => '1 column',
            '2' => '2 columns with map on the right'
        ];
    }
}
