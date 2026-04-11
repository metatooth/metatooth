<?php
/**
 * SquareUp
 *
 * InstallData Setup
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Customer\Model\Customer;

/**
 * Class InstallData
 */
class InstallData implements InstallDataInterface
{
    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * InstallData constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Installs data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        /** Add squareup_customer_id customer attribute */
        $eavSetup->addAttribute(
            Customer::ENTITY,
            'squareup_customer_id',
            [
                'type'             => 'text',
                'input'            => 'text',
                'label'            => 'Squareup Customer Id',
                'global'           => 1,
                'visible'          => 0,
                'required'         => 0,
                'user_defined'     => 0,
                'default'          => '',
                'visible_on_front' => 0,
                'source'           => null,
                'comment'          => 'Square Customer Id'
            ]
        );

        /** Add squareup_just_imported customer attribute */
        $eavSetup->addAttribute(
            Customer::ENTITY,
            'squareup_just_imported',
            [
                'type'             => 'int',
                'input'            => 'text',
                'label'            => 'Squareup Just Imported',
                'global'           => 1,
                'visible'          => 0,
                'required'         => 0,
                'user_defined'     => 0,
                'default'          => 0,
                'visible_on_front' => 0,
                'source'           => null,
                'comment'          => 'Squareup Just Imported'
            ]
        );

        $setup->endSetup();
    }
}
