<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Block;

/**
 * "Store Locator" link
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class TopLink extends \Magento\Framework\View\Element\Html\Link
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;

    /**
     * @var \Magebuzz\Storelocator\Helper\Data
     */
    protected $_storelocatorHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magebuzz\Storelocator\Helper\Data $storelocatorHelper
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magebuzz\Storelocator\Helper\Data $storelocatorHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_storelocatorHelper = $storelocatorHelper;
        $this->_moduleManager = $moduleManager;
        $this->_customerSession = $customerSession;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getHref()
    {
        return $this->getUrl('storelocator', ['_secure' => true]);
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getLabel()
    {
        return __('Store Locator');
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_storelocatorHelper->showTopLink() || !$this->_moduleManager->isOutputEnabled('Magebuzz_Storelocator')) {
            return '';
        }
        return parent::_toHtml();
    }
}
