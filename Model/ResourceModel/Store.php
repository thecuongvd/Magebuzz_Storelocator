<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;

/**
 * Storelocator store mysql resource
 */
class Store extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Review store table
     *
     * @var string
     */
    protected $_storelocatorStoreTable;

    /**
     * Review tag table
     *
     * @var string
     */
    protected $_storeTagTable;

    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param string|null $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $resourcePrefix = null
    )
    {
        $this->_storeManager = $storeManager;
        parent::__construct($context, $resourcePrefix);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('mb_store_locator', 'store_locator_id');
        $this->_storelocatorStoreTable = $this->getTable('mb_store_locator_store');
        $this->_storeTagTable = $this->getTable('mb_store_locator_tag');
    }


    /**
     * Actions after load
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magebuzz\Helpdesk\Model\Ticket $object
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object)
    {
        parent::_afterLoad($object);

        if (!$object->getId()) {
            return $this;
        }

        // load store available in stores
        $object->setStores($this->getStores((int)$object->getId()));

        // load tags
        $object->setTags(implode(',', $this->getTags((int)$object->getId())));

        return $this;
    }

    /**
     * Retrieve store IDs related to given rating
     *
     * @param  int $storeId
     * @return array
     */
    public function getStores($storeLocatorId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_storelocatorStoreTable),
            'store_id'
        )->where(
            'store_locator_id = ?',
            $storeLocatorId
        );
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @return array
     */
    public function getTags($storeLocatorId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_storeTagTable),
            'tag'
        )->where(
            'store_locator_id = ?',
            $storeLocatorId
        );
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Perform actions before object save
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        if ($object->hasData('stores') && is_array($object->getStores())) {
            $stores = $object->getStores();
            $stores[] = 0;
            $object->setStores($stores);
        } elseif ($object->hasData('stores')) {
            $object->setStores([$object->getStores(), 0]);
        }

        if ($object->hasData('tags') && is_array($object->getTags())) {
            $tags = $object->getTags();
            $object->setTags($tags);
        } elseif ($object->hasData('tags')) {
            $object->setTags([$object->getTags()]);
        }

        return $this;
    }

    /**
     * Perform actions after object save
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $connection = $this->getConnection();

        /**
         * save stores
         */
        $stores = $object->getStores();
        if (!empty($stores)) {
            $condition = ['store_locator_id = ?' => $object->getId()];
            $connection->delete($this->_storelocatorStoreTable, $condition);

            $insertedStoreIds = [];
            foreach ($stores as $storeId) {
                if (in_array($storeId, $insertedStoreIds)) {
                    continue;
                }

                $insertedStoreIds[] = $storeId;
                $storeInsert = ['store_id' => $storeId, 'store_locator_id' => $object->getId()];
                $connection->insert($this->_storelocatorStoreTable, $storeInsert);
            }
        }

        /**
         * save tags
         */
        $tags = $object->getTags();
        if (!empty($tags)) {
            $condition = ['store_locator_id = ?' => $object->getId()];
            $connection->delete($this->_storeTagTable, $condition);

            $insertedTags = [];
            foreach ($tags as $tag) {
                if (empty($tag) || in_array($tag, $insertedTags)) {
                    continue;
                }

                $insertedTags[] = $tag;
                $emailInsert = ['tag' => $tag, 'store_locator_id' => $object->getId()];
                $connection->insert($this->_storeTagTable, $emailInsert);
            }
        } else {
            $condition = ['store_locator_id = ?' => $object->getId()];
            $connection->delete($this->_storeTagTable, $condition);
        }

        return $this;
    }
}
