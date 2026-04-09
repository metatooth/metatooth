<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/7/2018
 * Time: 6:07 PM
 */

namespace Squareup\Omni\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;

class Uninstall implements UninstallInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Uninstall script
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Invoked when remove-data flag is set during module uninstall.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        /** Remove squareup_customer_id customer attribute */
        $eavSetup->removeAttribute(Customer::ENTITY, 'squareup_customer_id');
        /** Remove squareup_just_imported customer attribute */
        $eavSetup->removeAttribute(Customer::ENTITY, 'squareup_just_imported');

        /**
         * Version 0.1.1
         * Remove customer and product attributes
         */
        $eavSetup->removeAttribute(Product::ENTITY, 'square_id');
        $eavSetup->removeAttribute(Product::ENTITY, 'square_variation_id');
        $eavSetup->removeAttribute(Product::ENTITY, 'square_variation');
        $eavSetup->removeAttribute(Customer::ENTITY, 'square_updated_at');

        /**
         * Version 0.1.4
         * Drop squareup_omni_refunds table
         */
        $setup->getConnection()->dropTable($this->resourceConnection->getTableName('squareup_omni_refunds'));

        /**
         * Version 0.1.3
         * Add squareup_omni_inventory table
         */
        $setup->getConnection()->dropTable($this->resourceConnection->getTableName('squareup_omni_inventory'));

        /**
         * Version 0.1.2
         * Add squareup_omni_transaction table
         */
        $setup->getConnection()->dropTable($this->resourceConnection->getTableName('squareup_omni_transaction'));

        /**
         * Version 0.1.1
         * Drop squareup_omni_location table
         */
        $setup->getConnection()->dropTable($this->resourceConnection->getTableName('squareup_omni_location'));

        /** Remove squareup_nonce column to quote_payment table */
        $setup->getConnection()->dropColumn('quote_payment', 'squareup_nonce');

        /** Remove squareup_transaction column to quote_payment table */
        $setup->getConnection()->dropColumn('quote_payment', 'squareup_transaction');

        /** Remove save_square_card column to quote_payment */
        $setup->getConnection()->dropColumn('quote_payment', 'save_square_card');

        /** Remove squareup_nonce column to sales_order_payment table */
        $setup->getConnection()->dropColumn('sales_order_payment', 'squareup_nonce');

        /** Remove squareup_transaction column to sales_order_payment table */
        $setup->getConnection()->dropColumn('sales_order_payment', 'squareup_transaction');

        /** Remove save_square_card column to sales_order_payment */
        $setup->getConnection()->dropColumn('sales_order_payment', 'save_square_card');

        /** Remove square_order_id column to sales_order table */
        $setup->getConnection()->dropColumn('sales_order', 'square_order_id');

        $setup->endSetup();
    }
}
