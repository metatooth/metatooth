<?php
/**
 * SquareUp
 *
 * Catalog Model
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
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Helper\Mapping;
use Squareup\Omni\Model\Catalog\Export;
use Squareup\Omni\Model\Catalog\Import;
use Squareup\Omni\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Class Catalog
 */
class Catalog extends Square
{
    /**
     * @var Export
     */
    private $export;

    /**
     * @var Import
     */
    private $import;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * Catalog constructor
     *
     * @param Export $export
     * @param Import $import
     * @param ProductResource $productResource
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
        Export $export,
        Import $import,
        ProductResource $productResource,
        Config $config,
        Logger $logger,
        Data $helper,
        Mapping $mapping,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->export = $export;
        $this->import = $import;
        $this->productResource = $productResource;
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

    /**
     * Check if product exists
     *
     * @param $id
     *
     * @return string
     */
    public function productExists($id)
    {
        return $this->productResource->productExists($id);
    }
}
