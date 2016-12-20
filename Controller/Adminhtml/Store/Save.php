<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Controller\Adminhtml\Store;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\TestFramework\ErrorLog\Logger;

class Save extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Date filter instance
     *
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $_dateFilter;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * File system
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;
    /**
     * File Uploader factory
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    protected $_storelocatorHelper;

    /**
     * @param Action\Context $context
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Date $dateFilter,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magebuzz\Storelocator\Helper\Data $storelocatorHelper
    )
    {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->_dateFilter = $dateFilter;
        $this->_date = $date;
        $this->_fileSystem = $fileSystem;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_logger = $logger;
        $this->_storelocatorHelper = $storelocatorHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebuzz_Storelocator::mange_store_locator');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            /** @var \Magebuzz\Storelocator\Model\Store $model */
            $model = $this->_objectManager->create('Magebuzz\Storelocator\Model\Store');

            $id = $this->getRequest()->getParam('store_locator_id');

            if ($id) {
                $model->load($id);
                if ($id != $model->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The wrong store is specified.'));
                }
            }

            if (!$data['longitude'] || !$data['latitude'] || $data['address'] != $model->getAddress()) {
                $address = urlencode($data['address']);
                $json = $this->_storelocatorHelper->getJsonData($address);
                $data['latitude'] = strval($json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'});
                $data['longitude'] = strval($json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'});
            }

            //upload icon
            $path = $this->_fileSystem->getDirectoryRead(DirectoryList::MEDIA)
                    ->getAbsolutePath('magebuzz/storelocator/');
            if (empty($data['is_delete_icon'])) {
                $storeImage = !empty($_FILES['icon']['name']);
                try {
                    // remove the old file
                    if ($storeImage) {
                        $oldName = !empty($data['old_icon']) ? $data['old_icon'] : '';
                        if ($oldName) {
                            @unlink($path . $oldName);
                        }

                        //find the first available name
                        $newName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $_FILES['icon']['name']);
                        if (substr($newName, 0, 1) == '.') // all non-english symbols
                            $newName = 'store_' . $newName;
                        $i = 0;
                        while (file_exists($path . $newName)) {
                            $newName = ++$i . '_' . $newName;
                        }

                        /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
                        $uploader = $this->_fileUploaderFactory->create(['fileId' => 'icon']);
                        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                        $uploader->setAllowRenameFiles(true);
                        $uploader->save($path, $newName);

                        $data['icon'] = $newName;
                    } else {
                        $oldName = !empty($data['old_icon']) ? $data['old_icon'] : '';
                        $data['icon'] = $oldName;
                    }
                } catch (\Exception $e) {
                    if ($e->getCode() != \Magento\MediaStorage\Model\File\Uploader::TMP_NAME_EMPTY) {
                        $this->_logger->critical($e);
                    }
                }
            }
            
            //Process delete icon
            if (!empty($data['is_delete_icon'])) {
                $oldName = !empty($data['old_icon']) ? $data['old_icon'] : '';
                if ($oldName) {
                    @unlink($path . $oldName);
                }
                $data['icon'] = '';

            }

            $model->setData($data);

            $model->setStores($data['stores']);

            $tagArray = explode(',', $data['tags']);
            $tagArray = array_map('trim', $tagArray);
            $model->setTags($tagArray);

            $this->_eventManager->dispatch(
                'storelocator_store_prepare_save',
                ['store' => $model, 'request' => $this->getRequest()]
            );

            try {
                $model->save();
                $this->messageManager->addSuccess(__('You saved this Store.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['store_locator_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the store.'));
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['store_locator_id' => $this->getRequest()->getParam('store_locator_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
