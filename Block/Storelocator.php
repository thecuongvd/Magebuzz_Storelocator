<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Block;

class Storelocator extends \Magento\Framework\View\Element\Template
{
    /**
     * Registry object.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * block collecion factory.
     *
     * @var \Magebuzz\Storelocator\Model\ResourceModel\Store\CollectionFactory
     */
    protected $_storeCollectionFactory;

    /**
     * block collecion factory.
     *
     * @var \Magebuzz\Storelocator\Model\ResourceModel\Tag\CollectionFactory
     */
    protected $_tagCollectionFactory;

    /**
     * scope config.
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * stdlib timezone.
     *
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_stdTimezone;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_sessionManager;

    protected $_storelocatorHelper;

    protected $_stores;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magebuzz\Storelocator\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $_stdTimezone
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Stdlib\DateTime\Timezone $_stdTimezone,
        \Magento\Customer\Model\Session $customerSession,
        \Magebuzz\Storelocator\Model\ResourceModel\Store\CollectionFactory $storeCollectionFactory,
        \Magebuzz\Storelocator\Model\ResourceModel\Tag\CollectionFactory $tagCollectionFactory,
        \Magebuzz\Storelocator\Helper\Data $storelocatorHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_stdTimezone = $_stdTimezone;
        $this->_customerSession = $customerSession;
        $this->_sessionManager = $context->getSession();
        $this->_storeCollectionFactory = $storeCollectionFactory;
        $this->_tagCollectionFactory = $tagCollectionFactory;
        $this->_storelocatorHelper = $storelocatorHelper;

        $this->_scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $context->getStoreManager();

        $pageType = $this->_storelocatorHelper->getPageType();
        if ($pageType == 2) {
            $this->setTemplate('2columns.phtml');
        } else {
            $this->setTemplate('1column.phtml');
        }
        
    }

    public function getStorelocatorHelper()
    {
        return $this->_storelocatorHelper;
    }

    public function getScriptUrl()
    {
        return "//maps.googleapis.com/maps/api/js?key="
        . $this->_storelocatorHelper->getGoogleApiKey()
        . "&sensor=false&libraries=geometry,places";
    }

    public function getStoreQuery()
    {
        return $this->_sessionManager->getData('store_query');
    }

    public function displaySearchForm()
    {
        return $this->_storelocatorHelper->displaySearchForm();
    }

    public function _prepareLayout()
    {
        parent::_prepareLayout();

        $showPerPageOptions = $this->_storelocatorHelper->getShowPerPageOptions();
        if (empty($showPerPageOptions)) {
            $showPerPageOptions = [10 => 10, 20 => 20, 30 => 30];
        }

        if ($this->getStores()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'storelocator.store.pager'
            )->setAvailableLimit($showPerPageOptions);

            $defaultLimit = $this->_storelocatorHelper->getDefaultShowPerPage();
            if (!$limit = $this->getRequest()->getParam($pager->getLimitVarName()) && $defaultLimit) {
                $pager->setLimit($defaultLimit);
            }

            $pager->setShowAmounts(false)
                ->setShowPerPage(true)
                ->setCollection($this->getStores());

            $this->setChild('pager', $pager);
            $this->getStores()->load();
        }

        return $this;
    }

    public function getStores()
    {
        if ($this->_stores === null) {
            $data = $this->getRequest()->getParams();
            $_collection = $this->_storeCollectionFactory->create()
                ->addFieldToSelect(
                    '*'
                );

            $centerLatitude = $_collection->getFirstItem()->getLatitude();
            $centerLongitude = $_collection->getFirstItem()->getLongitude();
            $this->_sessionManager->setData('centerLatitude', $centerLatitude);
            $this->_sessionManager->setData('centerLongitude', $centerLongitude);
            $this->_sessionManager->setData('store_query', '');

            if (!empty($data['q'])) {
                if (!empty($data['d'])) {
                    $radius = $data['d'];
                } else {
                    $radius = $this->_storelocatorHelper->getSearchRadius();
                }
                $storeIds = [];
                if ($data['q']) {
                    $address = urlencode($data['q']);
                    $json = $this->_storelocatorHelper->getJsonData($address);
                    $centerLatitude = strval($json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'});
                    $centerLongitude = strval($json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'});
                    foreach ($_collection as $item) {
                        if ($this->_storelocatorHelper->distanceBetweenTwoCoord($item->getLatitude(), $item->getLongitude(), $centerLatitude, $centerLongitude) <= $radius)
                            $storeIds[] = $item->getId();
                    }
                }
                $_collection->addFieldToFilter('store_locator_id', array('in' => $storeIds));
                $this->_sessionManager->setData('store_query', $data['q']);
                $this->_sessionManager->setData('centerLatitude', $centerLatitude);
                $this->_sessionManager->setData('centerLongitude', $centerLongitude);
                $_collection->addExpressionFieldToSelect('distance', '(3956 * 2 * ASIN(SQRT( POWER(SIN(({{latitude}} - ' . $centerLatitude . ') *  pi()/180 / 2), 2) +COS({{latitude}} * pi()/180) * COS(' . $centerLatitude . ' * pi()/180) * POWER(SIN(({{longitude}} - ' . $centerLongitude . ') * pi()/180 / 2), 2) )))', array('latitude' => 'latitude', 'longitude' => 'longitude'));
                $_collection->getSelect()->order('distance', 'ASC');
            }

            if (!empty($data['tag'])) {
                // filter collection by tag
                $listStoreIds = $this->_storelocatorHelper->filterStoreByTag($data['tag']);
                $_collection->addFieldToFilter('store_locator_id', array('in' => $listStoreIds));
            }

            if (isset($data['current_latitude']) && isset($data['current_longitude'])) {
                // find the neariest store
                if ($data['current_latitude'] && $data['current_longitude']) {
                    $centerLatitude = strval($data['current_latitude']);
                    $centerLongitude = strval($data['current_longitude']);
                    $this->_sessionManager->setData('store_query', "HERE");
                    $this->_sessionManager->setData('centerLatitude', $centerLatitude);
                    $this->_sessionManager->setData('centerLongitude', $centerLongitude);
                    $_collection->addExpressionFieldToSelect('distance', '(3956 * 2 * ASIN(SQRT( POWER(SIN(({{latitude}} - ' . $centerLatitude . ') *  pi()/180 / 2), 2) +COS({{latitude}} * pi()/180) * COS(' . $centerLatitude . ' * pi()/180) * POWER(SIN(({{longitude}} - ' . $centerLongitude . ') * pi()/180 / 2), 2) )))', array('latitude' => 'latitude', 'longitude' => 'longitude'));
                    $_collection->getSelect()->order('distance', 'ASC');
                }
            }

            $this->_stores = $_collection;
        }

        return $this->_stores;
    }

    public function getStoreLimit()
    {
        return $this->_sessionManager->getData('store_query');
    }

    public function getDistancesToStores()
    {
        $centerLatitude = $this->_sessionManager->getData('centerLatitude');
        $centerLongitude = $this->_sessionManager->getData('centerLongitude');

        if (isset($centerLatitude) && isset($centerLongitude)) {
            $storeDistances = [];

            foreach ($this->getStores()->getData() as $item) {
                $storeDistances[$item['store_locator_id']] = $this->_storelocatorHelper->distanceBetweenTwoCoord($item['latitude'], $item['longitude'], $centerLatitude, $centerLongitude);
            }
            asort($storeDistances);

            return $storeDistances;
        }
    }

    public function getSearchQueryText()
    {
        return (string)$this->getRequest()->getParam('q');
    }

    public function getSearchRadius()
    {
        return (string)$this->getRequest()->getParam('d');
    }

    public function getDefaultLatLong()
    {
        $helper = $this->_storelocatorHelper;
        $defaultLocation = $helper->getDefaultAddress();
        $defaultLatLong = [];

        //set default location by address
        if ($defaultLocation != '') {
            $address = urlencode($defaultLocation);
            $json = $helper->getJsonData($address);
            $defaultLatLong = array('lat' => strval($json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'}), 'long' => strval($json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'}));
        }

        $data = $this->getRequest()->getParams();
        if ((!empty($data['q'])) || ($defaultLocation == '')) {
            //searching - display default by result
            if ($this->getStores()->getSize()) {
                $nearestStore = $this->getStores()->getFirstItem();
                $defaultLatLong = array('lat' => $nearestStore->getLatitude(), 'long' => $nearestStore->getLongitude(),);
            }
        }

        if (empty($defaultLatLong)) {
            // if it is empty, set to a default location to avoid problem
            $defaultLatLong = array('lat' => '29.737354', 'long' => '-95.416767');
        }

        return $defaultLatLong;
    }

    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    public function showEmailAndWebsite()
    {
        return $this->_storelocatorHelper->showEmailAndWebsite();
    }

    public function distance($lat1, $lon1, $lat2, $lon2, $unit)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        if ($unit == 1) {
            return ($miles * 1.609344);
        } else {
            return $miles;
        }
    }

    public function getSearchRadiusUnit()
    {
        return $this->_storelocatorHelper->getRadiusUnit();
    }

    public function getSearchRadiusOptions()
    {
        return $this->_storelocatorHelper->getSearchRadiusOptions();
    }

    public function getListTag()
    {
        $storeCollection = $this->getStores();
        $storeIds = $storeCollection->getColumnValues('store_locator_id');

        $tagCollection = $this->_tagCollectionFactory->create()
            ->addFieldToFilter('store_locator_id', array('in' => $storeIds));

        $tags = [];
        if (count($tagCollection)) {
            foreach ($tagCollection as $tag) {
                $_newTag = strtolower(trim($tag->getTag()));
                if (!in_array($_newTag, $tags)) {
                    $tags[] = $_newTag;
                }
            }
        }

        return $tags;
    }

    /**
     * Get components configuration
     * @return array
     */
    public function getWidgetInitOptions()
    {
        return [
            'suggest' => [
                'template' => '[data-template=search-suggest]',
                'termAjaxArgument' => 'q',
                'source' => $this->getUrl('storelocator/index/storeSearch'),
                'filterProperty' => 'name',
                'valueField' => '#mb-query-search',
                'preventClickPropagation' => false,
                'minLength' => 2,
            ]
        ];
    }

    public function getCommonIconUrl()
    {
        return $this->getViewFileUrl('Magebuzz_Storelocator::images/common_store_icon.png');
    }
    
    public function getIconUrl($icon)
    {
        $iconUrl = $this->_storelocatorHelper->getIconUrl($icon);
        if (!$iconUrl) {
            $iconUrl = $this->getViewFileUrl('Magebuzz_Storelocator::images/default_store_icon.jpg');
        }
        return $iconUrl;
    }
}