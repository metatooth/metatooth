<?php
/**
 * SquareUp
 *
 * UpgradeSchema Setup
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /**
         * Version 0.1.1
         * Add squareup_omni_location table
         */
        if (version_compare($context->getVersion(), '0.1.1') < 0) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('squareup_omni_location')
            )->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true
                ],
                'Id'
            )->addColumn(
                'square_id',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true
                ],
                'Square Id'
            )->addColumn(
                'name',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => true
                ],
                'Name'
            )->addColumn(
                'address_line_1',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true,
                ],
                'Address Line 1'
            )->addColumn(
                'locality',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true,
                ],
                'Locality'
            )->addColumn(
                'administrative_district_level_1',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true,
                ],
                'Administrative District Level 1'
            )->addColumn(
                'postal_code',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true,
                ],
                'Postal Code'
            )->addColumn(
                'country',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true
                ],
                'Country'
            )->addColumn(
                'phone_number',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true,
                ],
                'Phone Number'
            )->addColumn(
                'status',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => false
                ],
                'Status'
            )->addColumn(
                'currency',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false
                ],
                'Status'
            )->addIndex(
                $setup->getIdxName('squareup_omni_location', ['square_id']),
                ['square_id']
            )->setComment(
                'Squareup Omni Location'
            );

            $setup->getConnection()->createTable($table);
        }

        /**
         * Version 0.1.2
         * Add squareup_omni_transaction table
         */
        if (version_compare($context->getVersion(), '0.1.2') < 0) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('squareup_omni_transaction')
            )->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true
                ],
                'Id'
            )->addColumn(
                'square_id',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false,
                ],
                'Square Id'
            )->addColumn(
                'location_id',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false,
                ],
                'Location Id'
            )->addColumn(
                'amount',
                Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Amount Money'
            )->addColumn(
                'processing_fee_amount',
                Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Processing Fee Money'
            )->addColumn(
                'type',
                Table::TYPE_SMALLINT,
                null,
                [
                    'nullable' => true,
                ],
                'Type'
            )->addColumn(
                'card_brand',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => true,
                ],
                'Card Brand'
            )->addColumn(
                'note',
                Table::TYPE_TEXT,
                null,
                [
                    'nullable' => true,
                ],
                'Note'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable' => true,
                ],
                'Created At'
            )->addColumn(
                'tender_id',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false,
                ],
                'Tender Id'
            );

            $setup->getConnection()->createTable($table);
        }

        /**
         * Version 0.1.3
         * Add squareup_omni_inventory table
         */
        if (version_compare($context->getVersion(), '0.1.3') < 0) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('squareup_omni_inventory')
            )->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true
                ],
                'Id'
            )->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => false,
                ],
                'Product Id'
            )->addColumn(
                'location_id',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false,
                ],
                'Square Location Id'
            )->addColumn(
                'status',
                Table::TYPE_TEXT,
                40,
                [
                    'nullable' => false,
                ],
                'Status'
            )->addColumn(
                'quantity',
                Table::TYPE_INTEGER,
                null,
                [
                    'nullable' => true,
                ],
                'Quantity'
            )->addColumn(
                'calculated_at',
                Table::TYPE_DATETIME,
                null,
                [
                    'nullable' => true,
                ],
                'Calculated At'
            )->addColumn(
                'received_at',
                Table::TYPE_DATETIME,
                null,
                [
                    'nullable' => false,
                ],
                'Received At'
            )->addIndex(
                $setup->getIdxName('squareup_omni_inventory', ['product_id']),
                ['product_id']
            );

            $setup->getConnection()->createTable($table);
        }

        /**
         * Version 0.1.4
         * Add squareup_omni_refunds table
         */
        if (version_compare($context->getVersion(), '0.1.4') < 0) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('squareup_omni_refunds')
            )->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true
                ],
                'Id'
            )->addColumn(
                'square_id',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable'  => false,
                ],
                'Square Id'
            )->addColumn(
                'location_id',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable'  => false,
                ],
                'Location Id'
            )->addColumn(
                'transaction_id',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable'  => false,
                ],
                'Transaction Id'
            )->addColumn(
                'tender_id',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable'  => false,
                ],
                'Tender Id'
            )->addColumn(
                'amount',
                Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Amount Money'
            )->addColumn(
                'processing_fee_amount',
                Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Processing Fee Money'
            )->addColumn(
                'reason',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable'  => false,
                ],
                'Reason'
            )->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                [
                    'nullable'  => true,
                ],
                'Status'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                [
                    'nullable'  => true,
                ],
                'Created At'
            );

            $setup->getConnection()->createTable($table);
        }

        /** Add customer_square_id column to squareup_omni_transaction table */
        if (version_compare($context->getVersion(), '0.2.6') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('squareup_omni_transaction'),
                'customer_square_id',
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => 255,
                    'nullable' => true,
                    'comment'  => 'Customer Square Id'
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.2.13') < 0) {
            try {
                $table = $setup->getConnection()->newTable(
                    $setup->getTable('squareup_omni_config')
                )->addColumn(
                    'config_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary'  => true
                    ],
                    'Config Id'
                )->addColumn(
                    'scope',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Scope'
                )->addColumn(
                    'path',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Path'
                )->addColumn(
                    'value',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'Value'
                );

                $setup->getConnection()->createTable($table);
            } catch (\Exception $exception) {

            }
        }

        if (version_compare($context->getVersion(), '0.3.1') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('squareup_omni_config'),
                'scope_id',
                [
                    'type'     => Table::TYPE_INTEGER,
                    'nullable' => false,
                    'comment'  => 'Scope Id'
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.3.5') < 0) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable(\Squareup\Omni\Model\ResourceModel\GiftCard::TABLE_NAME)
            )->addColumn(
                \Squareup\Omni\Model\ResourceModel\GiftCard::ENTITY_ID,
                Table::TYPE_INTEGER,
                11,
                [
                    'unsigned' => true,
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'Entity Id'
            )->addColumn(
                'quote_id',
                Table::TYPE_INTEGER,
                10,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Quote Id'
            )->addColumn(
                'card_code',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => false
                ],
                'Card code'
            )->addColumn(
                'card_nonce',
                Table::TYPE_TEXT,
                255,
                [
                    'nullable' => true
                ],
                'Card nonce'
            )->addColumn(
                'current_amount',
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => false
                ],
                'Card current amount'
            )->addColumn(
                'amount',
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => true
                ],
                'Card amount'
            )->addForeignKey(
                $setup->getFkName(
                    'squareup_omni_giftcard',
                    'quote_id',
                    'quote',
                    'entity_id'
                ),
                'quote_id',
                $setup->getTable('quote'),
                'entity_id',
                Table::ACTION_CASCADE
            );

            $setup->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '0.3.4') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('squareup_omni_location'),
                'webhook_time',
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => 255,
                    'nullable' => true,
                    'comment'  => 'Webhook time'
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.3.6') < 0) {
            $table = $setup->getConnection()->newTable(
                $setup->getTable('squareup_omni_giftcard_refunds')
            )->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                11,
                [
                    'unsigned' => true,
                    'identity' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'Entity Id'
            )->addColumn(
                'creditmemo_id',
                Table::TYPE_INTEGER,
                11,
                [
                    'nullable' => false
                ],
                'Creditmemo Id'
            )->addColumn(
                'card_id',
                Table::TYPE_INTEGER,
                11,
                [
                    'nullable' => false
                ],
                'Card Id'
            )->addColumn(
                'amount',
                Table::TYPE_DECIMAL,
                '12,4',
                [
                    'nullable' => false
                ],
                'Amount'
            );

            $setup->getConnection()->createTable($table);
        }

        if (version_compare($context->getVersion(), '0.3.7') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('sales_creditmemo'),
                'giftcard_refunds',
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => 255,
                    'nullable' => false,
                    'comment'  => 'Giftcard Refunds'
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.3.8') < 0) {
            $setup->getConnection()->addColumn(
                $setup->getTable('squareup_omni_location'),
                'cc_processing',
                [
                    'type'     => Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'comment'  => 'Credit Card Processing'
                ]
            );
        }

        $setup->endSetup();
    }
}
