<?php
/**
 * SquareUp
 *
 * Inventory Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Helper\Mapping;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\Inventory\Import;
use Squareup\Omni\Model\Inventory\Export;

/**
 * Class Inventory
 */
class Inventory extends Square
{
    /**
     * @var Import
     */
    private $import;

    /**
     * @var Export
     */
    private $export;

    /**
     * Inventory constructor
     *
     * @param Import $import
     * @param Export $export
     * @param Config $config
     * @param Logger $logger
     * @param Data $helper
     * @param Mapping $mapping
     * @param Context $context
     * @param Registry $registry
     * @param null $resource
     * @param null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Import $import,
        Export $export,
        Config $config,
        Logger $logger,
        Data $helper,
        Mapping $mapping,
        Context $context,
        Registry $registry,
        $resource = null,
        $resourceCollection = null,
        array $data = []
    ) {
        $this->import = $import;
        $this->export = $export;
        parent::__construct(
            $config,
            $logger,
            $helper,
            $mapping,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(\Squareup\Omni\Model\ResourceModel\Inventory::class);
    }

    /**
     * Start process
     *
     * @return bool
     */
    public function start()
    {
        if (\Squareup\Omni\Model\System\Config\Source\Options\Records::SQUARE == $this->configHelper->getSor()) {
            $this->import->start();
        } else {
            $this->export->start();
        }

        return true;
    }
}
