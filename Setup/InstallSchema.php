<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Storelocator\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'mb_store_locator'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mb_store_locator'))
            ->addColumn(
                'store_locator_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Store Id'
            )
            ->addColumn(
                'title',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Title'
            )
            ->addColumn(
                'email',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Email'
            )
            ->addColumn(
                'phone',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Phone'
            )
            ->addColumn(
                'website',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Website'
            )
            ->addColumn(
                'postal_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Postal Code'
            )
            ->addColumn(
                'address',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Address'
            )
            ->addColumn(
                'longitude',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Longitude'
            )
            ->addColumn(
                'latitude',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Latitude'
            )
            ->addColumn(
                'icon',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Icon'
            )
            ->addColumn(
                'description',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                '64k',
                [],
                'Description'
            )
            ->addColumn(
                'is_active',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Is Active'
            )
            ->addIndex(
                $installer->getIdxName('mb_store_locator', ['postal_code', 'address', 'longitude', 'latitude', 'is_active']),
                ['postal_code', 'address', 'longitude', 'latitude', 'is_active']
            )
            ->addIndex(
                $setup->getIdxName(
                    $installer->getTable('mb_store_locator'),
                    ['title'],
                    AdapterInterface::INDEX_TYPE_FULLTEXT
                ),
                ['title'],
                ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
            )
            ->setComment('Store Locator');

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'mb_store_locator_store'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mb_store_locator_store'))
            ->addColumn(
                'store_locator_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Store Id'
            )
            ->addColumn(
                'store_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Store Id'
            )
            ->addIndex(
                $installer->getIdxName('mb_store_locator_store', ['store_locator_id']),
                ['store_locator_id']
            )
            ->addForeignKey(
                $installer->getFkName('mb_store_locator_store', 'store_locator_id', 'mb_store_locator', 'store_locator_id'),
                'store_locator_id',
                $installer->getTable('mb_store_locator'),
                'store_locator_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->addForeignKey(
                $installer->getFkName('mb_store_locator_store', 'store_id', 'store', 'store_id'),
                'store_id',
                $installer->getTable('store'),
                'store_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Store Locator To Stores Relations');

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'mb_store_locator_tag'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('mb_store_locator_tag'))
            ->addColumn(
                'tag_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Tag Id'
            )
            ->addColumn(
                'store_locator_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Store Locator Id'
            )
            ->addColumn(
                'tag',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => '', 'primary' => true],
                'Tag'
            )
            ->addIndex(
                $installer->getIdxName('mb_store_locator_tag', ['tag', 'store_locator_id']),
                ['tag', 'store_locator_id']
            )
            ->addForeignKey(
                $installer->getFkName('mb_store_locator_tag', 'store_locator_id', 'mb_store_locator', 'store_locator_id'),
                'store_locator_id',
                $installer->getTable('mb_store_locator'),
                'store_locator_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            )
            ->setComment('Tags');

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
