<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Block\Adminhtml\Store;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    )
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve text for header element depending on loaded store
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('storelocator_store')->getId()) {
            return __("Edit Store '%1'", $this->escapeHtml($this->_coreRegistry->registry('storelocator_store')->getTitle()));
        } else {
            return __('New Store');
        }
    }

    /**
     * Initialize storelocator store edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'store_locator_id';
        $this->_blockGroup = 'Magebuzz_Storelocator';
        $this->_controller = 'adminhtml_store';

        parent::_construct();

        if ($this->_isAllowedAction('Magebuzz_Storelocator::save')) {
            $this->buttonList->update('save', 'label', __('Save Store'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Magebuzz_Storelocator::store_delete')) {
            $this->buttonList->update('delete', 'label', __('Delete Store'));
        } else {
            $this->buttonList->remove('delete');
        }

        if ($this->_coreRegistry->registry('storelocator_store')->getId()) {
            $this->buttonList->remove('reset');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('storelocator/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
}
