<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Block\Adminhtml;

class Store extends \Magento\Backend\Block\Widget\Grid\Container
{
    protected function _construct()
    {
        $this->_controller = 'adminhtml_store';
        $this->_blockGroup = 'Magebuzz_Storelocator';
        $this->_headerText = __('Manage Stores');

        parent::_construct();

        if ($this->_isAllowedAction('Magebuzz_Storelocator::save')) {
            $this->buttonList->update('add', 'label', __('Add New Store'));
        } else {
            $this->buttonList->remove('add');
        }
    }

    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
