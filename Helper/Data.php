<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Helper;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface;

/**
 * Catalog data helper
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Currently selected store ID if applicable
     *
     * @var int
     */
    protected $_storeId;

    /**
     *
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_backendUrl;

    /**
     * @var \Magento\Framework\DB\Helper
     */
    protected $_resourceHelper;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var \Magento\MediaStorage\Model\File\Uploader
     */
    protected $_uploaderFactory;

    /**
     * @var Filesystem
     */
    protected $_fileSystem;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    protected $_fileStorageDatabase;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface
     */
    protected $mediaDirectory;

    protected $_jsonEncoder;

    /**
     * category collection factory.
     *
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * block collecion factory.
     *
     * @var \Magebuzz\Storelocator\Model\ResourceModel\Tag\CollectionFactory
     */
    protected $_tagCollectionFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Registry $coreRegistry
     * @param CustomerSession $customerSession
     * @param \Magento\Framework\DB\Helper $resourceHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $coreRegistry,
        CustomerSession $customerSession,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDatabase,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magebuzz\Storelocator\Model\ResourceModel\Tag\CollectionFactory $tagCollectionFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    )
    {
        $this->_resource = $resource;
        $this->_storeManager = $storeManager;
        $this->_coreRegistry = $coreRegistry;
        $this->_customerSession = $customerSession;
        $this->_backendUrl = $backendUrl;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_tagCollectionFactory = $tagCollectionFactory;
        $this->_resourceHelper = $resourceHelper;
        $this->_uploaderFactory = $uploaderFactory;
        $this->_fileSystem = $fileSystem;
        $this->_fileStorageDatabase = $fileStorageDatabase;
        $this->_localeDate = $localeDate;
        $this->_jsonEncoder = $jsonEncoder;

        parent::__construct($context);

        $this->mediaDirectory = $this->_fileSystem->getDirectoryRead(DirectoryList::MEDIA);
    }

    /**
     * Set a specified store ID value
     *
     * @param int $store
     * @return $this
     */
    public function setStoreId($store)
    {
        $this->_storeId = $store;
        return $this;
    }

    public function getJsonData($address)
    {
        $googleGeoApiUrl = $this->getGoogleGeoApiUrl();

        if (!empty($googleGeoApiUrl)) {
            $url = $googleGeoApiUrl . "?address=$address&sensor=false";
        } else {
            $url = 'http://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&sensor=false';
        }

        $rCURL = curl_init();
        curl_setopt($rCURL, CURLOPT_URL, $url);
        curl_setopt($rCURL, CURLOPT_HEADER, 0);
        curl_setopt($rCURL, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
        curl_setopt($rCURL, CURLOPT_RETURNTRANSFER, 1);
        $aData = curl_exec($rCURL);
        curl_close($rCURL);

        $json = json_decode($aData);

        return $json;
    }

    /*
     * @return string
     */
    public function getIconUrl($icon)
    {
        $folderName = 'magebuzz/storelocator';

        if ($icon) {
            $iconPath = $folderName . '/' . $icon;
            $iconUrl = $this->_storeManager->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $iconPath;

            if ($this->checkIsFile($iconPath)) {
                return $iconUrl;
            }
        }

        $defaultIcon = $this->getDefaultStoreIcon();
        $defaultIconPath = $folderName . '/' . $defaultIcon;
        $defaultIconUrl = $this->_storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $defaultIconPath;

        if ($defaultIcon !== null && $this->checkIsFile($defaultIconPath)) {
            return $defaultIconUrl;
        }

        return '';
    }

    /**
     * If DB file storage is on - find there, otherwise - just file_exists
     *
     * @param string $filename relative file path
     * @return bool
     */
    protected function checkIsFile($filename)
    {
        if ($this->_fileStorageDatabase->checkDbUsage() && !$this->mediaDirectory->isFile($filename)) {
            $this->_fileStorageDatabase->saveFileToFilesystem($filename);
        }
        return $this->mediaDirectory->isFile($filename);
    }

    /*public function enabledModule()
    {
        return (boolean)$this->scopeConfig->getValue(
            'storelocator/general/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }*/

    public function getGoogleApiKey()
    {
        return $this->scopeConfig->getValue(
            'storelocator/general/google_api_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getGoogleGeoApiUrl()
    {
        return $this->scopeConfig->getValue(
            'storelocator/general/google_geo_api_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function showTopLink()
    {
        return (boolean)$this->scopeConfig->getValue(
            'storelocator/general/show_top_link',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getDefaultAddress()
    {
        return (string)$this->scopeConfig->getValue(
            'storelocator/general/default_location_address',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function showEmailAndWebsite()
    {
        return (boolean)$this->scopeConfig->getValue(
            'storelocator/general/show_email_and_website',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getSearchRadius()
    {
        return (int)$this->scopeConfig->getValue(
            'storelocator/general/default_search_radius',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getRadiusUnit()
    {
        return (string)$this->scopeConfig->getValue(
            'storelocator/general/distance_units',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getSearchRadiusOptions()
    {
        $radius = $this->scopeConfig->getValue(
            'storelocator/general/search_radius',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );

        $options = [];

        if ($radius != '') {
            $radiusArr = explode(',', $radius);
            if (count($radiusArr)) {
                foreach ($radiusArr as $item) {
                    $options[] = ['value' => trim($item), 'label' => $this->getSearchLabel(trim($item))];
                }
            }
        }

        return $options;
    }

    public function getSearchLabel($radius)
    {
        $unit = $this->getRadiusUnit();

        $searchLabel = '';

        if ($unit == 1) {
            $searchLabel = $radius . ' Kilometers';
        } else {
            $searchLabel = $radius . ' Miles';
        }

        return $searchLabel;
    }

    public function renameImage($imageName)
    {
        $string = str_replace("  ", " ", $imageName);
        $newImageName = str_replace(" ", "-", $string);
        $newImageName = strtolower($newImageName);

        return $newImageName;
    }

    public function getDefaultStoreIcon()
    {
        return $this->scopeConfig->getValue(
            'storelocator/general/default_store_icon',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function enableSearchSuggestion()
    {
        return (boolean)$this->scopeConfig->getValue(
            'storelocator/general/enable_search_suggestion',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function displaySearchForm()
    {
        return (boolean)$this->scopeConfig->getValue(
            'storelocator/general/show_search_form',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getPageType()
    {
        return $this->scopeConfig->getValue(
            'storelocator/general/page_type',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function getShowPerPageOptions()
    {
        $options = [];

        $showPerPage = $this->scopeConfig->getValue(
            'storelocator/general/show_per_page_values',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );

        if ($showPerPage != '') {
            $showPerPageOptions = explode(',', $showPerPage);
            if (!empty($showPerPageOptions)) {
                foreach ($showPerPageOptions as $option) {
                    $options[$option] = $option;
                }
            }
        }

        return $options;
    }

    public function getDefaultShowPerPage()
    {
        return (int)$this->scopeConfig->getValue(
            'storelocator/general/show_per_page',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function filterStoreByTag($tag)
    {
        $listTag = $this->_tagCollectionFactory->create()
            ->addFieldToFilter('tag', array('like' => '%' . $tag . '%'))
            ->groupStore('store_locator_id')
            ->getColumnValues('store_locator_id');

        return $listTag;
    }

    public function canShowTagFilter()
    {
        return (boolean)$this->scopeConfig->getValue(
            'storelocator/general/display_tag_filter',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeId
        );
    }

    public function distanceBetweenTwoCoord($lat1, $lon1, $lat2, $lon2)
    {
        if ($lat1 && $lon1 && $lat2 && $lon2) {
            $unit = $this->getRadiusUnit();
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
        } else {
            return 0;
        }
    }

    public function sortByValue(&$array, $key)
    {
        $sorter = [];
        $ret = [];

        reset($array);

        foreach ($array as $ii => $va) {
            $sorter[$ii] = $va[$key];
        }

        asort($sorter);

        foreach ($sorter as $ii => $va) {
            $ret[$ii] = $array[$ii];
        }

        $array = $ret;
    }
}
