<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Controller\Index;

use Magento\Framework\App\Action\Action;

class StoreSearch extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /** @var \Magento\Framework\UrlInterface */
    protected $_urlBuilder;

    protected $_storelocatorHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param array $searchModules
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\UrlFactory $urlFactory,
        \Magebuzz\Storelocator\Helper\Data $storelocatorHelper
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_storelocatorHelper = $storelocatorHelper;
        $this->_urlBuilder = $urlFactory->create();
    }

    /**
     * Global Search Action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $query = $this->getRequest()->getParam('q', '');
        //$query = 'Cambridge';
        $address = urlencode($query);

        $googleGeoApiUrl = $this->_storelocatorHelper->getGoogleGeoApiUrl();
        if ($googleGeoApiUrl != '') {
            $url = $googleGeoApiUrl . "?address=$address&sensor=false";
        } else {
            $url = 'http://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&sensor=false';
        }

        $rCURL = curl_init();
        curl_setopt($rCURL, CURLOPT_URL, $url);
        curl_setopt($rCURL, CURLOPT_HEADER, 0);
        curl_setopt($rCURL, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
        curl_setopt($rCURL, CURLOPT_RETURNTRANSFER, 1);
        $jsonData = curl_exec($rCURL);

        $data = json_decode($jsonData, TRUE);

        $result = [];
        foreach ($data['results'] as $item) {
            $result[] = [
                'address' => $item['formatted_address'],
                'url' => $this->_urlBuilder->getUrl('storelocator', ['_current' => true, 'q' => urlencode($item['formatted_address'])])
            ];
        }

        // return did you mean from item 2nd
        $result = array_slice($result, 1, 4);

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($result);
    }
}
