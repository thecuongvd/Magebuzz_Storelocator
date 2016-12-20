<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Model\Config\Source;

class DistanceUnit implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => '', 'value' => '--Select Unit--'],
            ['label' => 'Mile', 'value' => '0'],
            ['label' => 'Kilometer', 'value' => '1']
        ];
    }

    public function toArray()
    {
        return [
            '' => '--Select Type--',
            '0' => 'Mile',
            '1' => 'Kilometer'
        ];
    }
}
