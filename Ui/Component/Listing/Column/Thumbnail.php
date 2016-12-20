<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Thumbnail extends Column
{
    const NAME = 'icon';

    protected $storelocatorHelper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magebuzz\Storelocator\Helper\Data $storelocatorHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        \Magebuzz\Storelocator\Helper\Data $storelocatorHelper,
        array $components = [],
        array $data = []
    )
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->storelocatorHelper = $storelocatorHelper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $store = new \Magento\Framework\DataObject($item);
                $item[$fieldName . '_src'] = $this->storelocatorHelper->getIconUrl($store->getIcon());
                $item[$fieldName . '_alt'] = $store->getIcon();
                $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                    'storelocator/icon/edit',
                    ['id' => $store->getId()]
                );
                $item[$fieldName . '_orig_src'] = $this->storelocatorHelper->getIconUrl($store->getIcon());
            }
        }

        return $dataSource;
    }
}
