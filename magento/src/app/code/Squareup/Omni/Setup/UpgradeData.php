<?php
/**
 * SquareUp
 *
 * UpgradeData Setup
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Catalog\Model\Product;

/**
 * Class UpgradeData
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * UpgradeData constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Upgrades data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        /**
         * Version 0.1.1
         * Add customer and product attributes
         */
        if (version_compare($context->getVersion(), '0.1.1') < 0) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'square_id',
                [
                    'type'              => 'varchar',
                    'backend'           => '',
                    'frontend'          => '',
                    'label'             => 'Square Id',
                    'input'             => 'text',
                    'class'             => '',
                    'source'            => '',
                    'global'            => 0,
                    'visible'           => true,
                    'required'          => false,
                    'user_defined'      => true,
                    'default'           => '',
                    'searchable'        => false,
                    'filterable'        => false,
                    'comparable'        => false,
                    'visible_on_front'  => false,
                    'unique'            => true,
                    'system'            => false,
                    'used_in_product_listing' => true,
                    'group'             => 'General'
                ]
            );

            $eavSetup->addAttribute(
                Product::ENTITY,
                'square_variation_id',
                [
                    'type'              => 'varchar',
                    'backend'           => '',
                    'frontend'          => '',
                    'label'             => 'Square Variation Id',
                    'input'             => 'text',
                    'class'             => '',
                    'source'            => '',
                    'global'            => 0,
                    'visible'           => true,
                    'required'          => false,
                    'user_defined'      => true,
                    'default'           => null,
                    'searchable'        => false,
                    'filterable'        => false,
                    'comparable'        => false,
                    'visible_on_front'  => false,
                    'unique'            => true,
                    'system'            => false,
                    'used_in_product_listing' => true,
                    'group'             => 'General'
                ]
            );

            $eavSetup->addAttribute(
                Product::ENTITY,
                'square_variation',
                [
                    'type'              => 'int',
                    'backend'           => '',
                    'frontend'          => '',
                    'label'             => 'Square Variation',
                    'input'             => 'select',
                    'class'             => '',
                    'source'            => '',
                    'global'            => 0,
                    'is_configurable'   => true,
                    'apply_to'          => 'simple, configurable',
                    'visible'           => true,
                    'required'          => false,
                    'user_defined'      => true,
                    'default'           => null,
                    'searchable'        => false,
                    'filterable'        => false,
                    'comparable'        => false,
                    'visible_on_front'  => false,
                    'unique'            => false,
                    'system'            => false,
                    'used_in_product_listing' => true,
                    'group'             => 'General'
                ]
            );

            $eavSetup->addAttribute(
                Customer::ENTITY,
                'square_updated_at',
                [
                    'type'         => 'text',
                    'input'        => 'text',
                    'label'        => 'Square Updated At',
                    'global'       => 1,
                    'visible'      => 0,
                    'required'     => 0,
                    'user_defined' => 0,
                    'default'      => 0,
                    'visible_on_front' => 0,
                    'source'       => null,
                    'comment'      => 'Square Updated At'
                ]
            );
        }

        /**
         * Version 0.2.6
         * Add customer square_saved_cards attribute
         */
        if (version_compare($context->getVersion(), '0.2.6') < 0) {
            $eavSetup->addAttribute(
                Customer::ENTITY,
                'square_saved_cards',
                [
                    'type'         => 'text',
                    'input'        => 'text',
                    'label'        => 'Square Saved Cards',
                    'global'       => 1,
                    'visible'      => 0,
                    'required'     => 0,
                    'user_defined' => 0,
                    'default'      => null,
                    'visible_on_front' => 0,
                    'source'       => null,
                    'comment'      => 'Square Saved Cards'
                ]
            );
        }

        /**
         * Version 0.2.7
         * Add customer square_saved_cards attribute
         */
        if (version_compare($context->getVersion(), '0.2.7') < 0) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'square_locations',
                [
                    'type'              => 'varchar',
                    'backend'           => '',
                    'frontend'          => '',
                    'label'             => 'Square Locations',
                    'input'             => 'text',
                    'class'             => '',
                    'source'            => '',
                    'global'            => 0,
                    'visible'           => false,
                    'required'          => false,
                    'user_defined'      => true,
                    'default'           => '',
                    'searchable'        => false,
                    'filterable'        => false,
                    'comparable'        => false,
                    'visible_on_front'  => false,
                    'unique'            => true,
                    'system'            => false,
                    'is_used_in_grid'   => true,
                    'is_filterable_in_grid' => true,
                    'used_in_product_listing' => true,
                    'group'             => 'General'
                ]
            );

            $attributeSetIds = $eavSetup->getAllAttributeSetIds(Product::ENTITY);

            foreach ($attributeSetIds as $attributeSetId) {
                $eavSetup->addAttributeToSet(Product::ENTITY, $attributeSetId, '', 'square_locations');
            }
        }

        /**
         * Version 0.2.8
         * Add customer square_saved_cards attribute
         */
        if (version_compare($context->getVersion(), '0.2.8') < 0) {
            $eavSetup->removeAttribute(
                Product::ENTITY,
                'square_locations'
            );
        }

        /**
         * Version 0.2.9
         * Add customer square_saved_cards attribute
         */
        if (version_compare($context->getVersion(), '0.2.9') < 0) {
            $eavSetup->removeAttribute(
                Product::ENTITY,
                'square_variation'
            );

            $eavSetup->addAttribute(
                Product::ENTITY,
                'square_variation',
                [
                    'type'              => 'int',
                    'backend'           => '',
                    'frontend'          => '',
                    'label'             => 'Square Variation',
                    'input'             => 'select',
                    'class'             => '',
                    'source'            => '',
                    'global'            => 1,
                    'visible'           => true,
                    'required'          => false,
                    'user_defined'      => true,
                    'default'           => null,
                    'searchable'        => false,
                    'filterable'        => false,
                    'comparable'        => false,
                    'visible_on_front'  => false,
                    'unique'            => true,
                    'system'            => false,
                    'used_in_product_listing' => true,
                    'group'             => 'General'
                ]
            );
        }

        /**
         * Version 0.2.10
         * Change square_variation attribute
         */
        if (version_compare($context->getVersion(), '0.2.10') < 0) {
            $eavSetup->updateAttribute(
                Product::ENTITY,
                'square_variation',
                'unique',
                false
            );
        }

        if (version_compare($context->getVersion(), '0.2.11') < 0){
            $eavSetup->addAttribute(
                Product::ENTITY,
                'square_updated_at',
                [
                    'type'              => 'text',
                    'backend'           => '',
                    'frontend'          => '',
                    'label'             => 'Square Updated At',
                    'input'             => 'text',
                    'class'             => '',
                    'source'            => '',
                    'global'            => 1,
                    'visible'           => false,
                    'required'          => false,
                    'user_defined'      => false,
                    'default'           => 0,
                    'searchable'        => false,
                    'filterable'        => false,
                    'comparable'        => false,
                    'visible_on_front'  => false,
                    'unique'            => false,
                    'system'            => false,
                    'used_in_product_listing' => false,
                    'group'             => 'General'
                ]
            );
        }

        if (version_compare($context->getVersion(), '0.2.12') < 0){
            $eavSetup->addAttribute(
                Product::ENTITY,
                'square_product_image',
                [
                    'type'              => 'text',
                    'backend'           => '',
                    'frontend'          => '',
                    'label'             => 'Square Product Image',
                    'input'             => 'text',
                    'class'             => '',
                    'source'            => '',
                    'global'            => 1,
                    'visible'           => false,
                    'required'          => false,
                    'user_defined'      => true,
                    'default'           => 0,
                    'searchable'        => false,
                    'filterable'        => false,
                    'comparable'        => false,
                    'visible_on_front'  => false,
                    'unique'            => false,
                    'system'            => false,
                    'used_in_product_listing' => false,
                    'group'             => 'General'
                ]
            );
        }

        $setup->endSetup();
    }
}
