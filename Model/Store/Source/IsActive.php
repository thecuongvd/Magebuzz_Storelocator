<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Model\Store\Source;

class IsActive implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magebuzz\Storelocator\Model\Store
     */
    protected $_store;

    /**
     * Constructor
     *
     * @param \Magebuzz\Storelocator\Model\Store $store
     */
    public function __construct(\Magebuzz\Storelocator\Model\Store $store)
    {
        $this->_store = $store;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->_store->getAvailableStatuses();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
