<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Controller\Adminhtml\Store;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;

class Delete extends \Magento\Backend\App\Action
{
    /**
     * @param Action\Context $context
     */
    public function __construct(Action\Context $context)
    {
        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('store_locator_id');
        if ($id) {
            try {
                /** @var \Magebuzz\Storelocator\Model\Store $model */
                $model = $this->_objectManager->create('Magebuzz\Storelocator\Model\Store');
                $model->load($id);
                $model->delete();
                $this->_redirect('storelocator/*/');
                return;
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t delete this store right now. Please review the log and try again.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_redirect('storelocator/*/edit', ['store_locator_id' => $this->getRequest()->getParam('store_locator_id')]);
                return;
            }
        }
        $this->messageManager->addError(__('We can\'t find a rule to delete.'));
        $this->_redirect('storelocator/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebuzz_Storelocator::mange_store_locator');
    }
}
