<?php
/**
 * SquareUp
 *
 * InstallSchema Setup
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** Add squareup_nonce column to quote_payment table */
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_payment'),
            'squareup_nonce',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'comment'  => 'Squareup Nonce'
            ]
        );

        /** Add squareup_transaction column to quote_payment table */
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_payment'),
            'squareup_transaction',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'comment'  => 'Squareup Transaction'
            ]
        );

        /** Add save_square_card column to quote_payment */
        $setup->getConnection()->addColumn(
            $setup->getTable('quote_payment'),
            'save_square_card',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'comment'  => 'Save Square Card'
            ]
        );

        /** Add squareup_nonce column to sales_order_payment table */
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_payment'),
            'squareup_nonce',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'comment'  => 'Squareup Nonce'
            ]
        );

        /** Add squareup_transaction column to sales_order_payment table */
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_payment'),
            'squareup_transaction',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'comment'  => 'Squareup Transaction'
            ]
        );

        /** Add save_square_card column to sales_order_payment */
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_payment'),
            'save_square_card',
            [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'comment'  => 'Save Square Card'
            ]
        );

        /** Add square_order_id column to sales_order table */
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'square_order_id',
            [
                'type'     => Table::TYPE_TEXT,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Square Order ID'
            ]
        );

        $setup->endSetup();
    }
}
