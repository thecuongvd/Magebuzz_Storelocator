<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Block\Adminhtml\Store\Edit\Tab;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Main extends Generic implements TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $_groupRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Convert\DataObject
     */
    protected $_objectConverter;

    protected $_storelocatorHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Convert\DataObject $objectConverter
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Convert\DataObject $objectConverter,
        \Magento\Store\Model\System\Store $systemStore,
        \Magebuzz\Storelocator\Helper\Data $storelocatorHelper,
        array $data = []
    )
    {
        $this->_systemStore = $systemStore;
        $this->_groupRepository = $groupRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_objectConverter = $objectConverter;
        $this->_storelocatorHelper = $storelocatorHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare content for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __('Store Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Store Information');
    }

    /**
     * Returns status flag about this tab can be showed or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return Form
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /** @var \Magebuzz\Helpdesk\Model\Store $model */
        $model = $this->_coreRegistry->registry('storelocator_store');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('store_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getId()) {
            $fieldset->addField('store_locator_id', 'hidden', ['name' => 'store_locator_id']);
        }

        $fieldset->addField(
            'title',
            'text',
            ['name' => 'title', 'label' => __('Store Title'), 'title' => __('Store Title'), 'required' => true]
        );

        $fieldset->addField(
            'description',
            'textarea',
            ['name' => 'description', 'label' => __('Description'), 'title' => __('Description')]
        );

        $fieldset->addField(
            'email',
            'text',
            ['name' => 'email', 'label' => __('Email'), 'title' => __('Email'), 'class' => 'validate-email',]
        );

        $fieldset->addField(
            'website',
            'text',
            ['name' => 'website', 'label' => __('Website'), 'title' => __('Website')]
        );

        $fieldset->addField(
            'phone',
            'text',
            ['name' => 'phone', 'label' => __('Phone'), 'title' => __('Phone')]
        );

        $fieldset->addField(
            'postal_code',
            'text',
            ['name' => 'postal_code', 'label' => __('Postal Code'), 'title' => __('Postal Code'), 'class' => 'validate-number', 'required' => true]
        );

        $fieldset->addField(
            'address',
            'text',
            [
                'name' => 'address',
                'label' => __('Address'),
                'title' => __('Address'),
                'required' => true,
                'after_element_html' => __('<small>Leave 2 fields below empty if you do NOT know exact values.</small>')
            ]
        );

        $fieldset->addField(
            'latitude',
            'text',
            ['name' => 'latitude', 'label' => __('Latitude'), 'title' => __('Latitude'), 'class' => 'validate-number']
        );

        $fieldset->addField(
            'longitude',
            'text',
            ['name' => 'longitude', 'label' => __('Longitude'), 'title' => __('Longitude'), 'class' => 'validate-number']
        );

        $iconHtml = '';
        if ($model->getIcon()) {
            $iconHtml .= $this->getIconHtml('icon', $model->getIcon());
            $iconHtml .= $this->getDeleteCheckboxHtml();
        }
        $fieldset->addField(
            'icon',
            'file',
            [
                'name' => 'icon',
                'label' => __('Image'),
                'title' => __('Image'),
                'after_element_html' => $iconHtml
            ]
        );

        $fieldset->addField(
            'tags',
            'text',
            [
                'name' => 'tags',
                'label' => __('Tags'),
                'title' => __('Tags'),
                'after_element_html' => __('<small>Example : magebuzz,MageBuzz,Azebiz</small>')
            ]
        );

        /**
         * Check is single store mode
         */
        if (!$this->_storeManager->hasSingleStore()) {
            $field = $fieldset->addField(
                'select_stores',
                'multiselect',
                [
                    'label' => __('Store View'),
                    'required' => true,
                    'name' => 'stores[]',
                    'values' => $this->_systemStore->getStoreValuesForForm()
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
            );
            $field->setRenderer($renderer);
            $model->setSelectStores($model->getStores());
        } else {
            $fieldset->addField(
                'select_stores',
                'hidden',
                ['name' => 'stores[]', 'value' => $this->_storeManager->getStore(true)->getId()]
            );
            $model->setSelectStores($this->_storeManager->getStore(true)->getId());
        }

        $fieldset->addField(
            'is_active',
            'select',
            [
                'label' => __('Is Active'),
                'title' => __('Is Active'),
                'name' => 'is_active',
                'required' => true,
                'options' => ['1' => __('Yes'), '0' => __('No')]
            ]
        );

        if (!$model->getId()) {
            $model->setData('is_active', '1');
        }

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function getIconHtml($field, $icon)
    {
        $html = '';
        $html .= '<p style="margin-top: 5px">';
        $html .= '<image style="min-width:300px;" src="' . $this->_storelocatorHelper->getIconUrl($icon) . '" />';
        $html .= '<input type="hidden" value="' . $icon . '" name="old_' . $field . '"/>';
        $html .= '</p>';
        return $html;
    }
    
    protected function getDeleteCheckboxHtml()
    {
        $html = '';
        $html .= '<span class="delete-icon">'
            . '<input type="checkbox" name="is_delete_icon" class="checkbox" id="is_delete_icon">'
            . '<label for="is_delete_icon"> Delete Icon</label>'
            . '</span>';
        return $html;
    }
}
