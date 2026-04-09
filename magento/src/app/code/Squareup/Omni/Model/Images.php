<?php
/**
 * SquareUp
 *
 * Images Model
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
use Squareup\Omni\Model\Catalog\Images as CatalogImages;

/**
 * Class Images
 */
class Images extends Square
{
    /**
     * @var CatalogImages
     */
    private $catalogImages;

    /**
     * Images constructor
     *
     * @param Config $config
     * @param Logger $logger
     * @param Data $helper
     * @param Mapping $mapping
     * @param CatalogImages $catalogImages
     * @param Context $context
     * @param Registry $registry
     * @param null $resource
     * @param null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Config $config,
        Logger $logger,
        Data $helper,
        Mapping $mapping,
        CatalogImages $catalogImages,
        Context $context,
        Registry $registry,
        $resource = null,
        $resourceCollection = null,
        array $data = []
    ) {
        $this->catalogImages = $catalogImages;
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
     * @return bool
     */
    public function start()
    {

        if (\Squareup\Omni\Model\System\Config\Source\Options\Records::SQUARE == $this->configHelper->getSor()) {
            return false;
        }

        if (false === $this->configHelper->getEnableImages() || false === $this->configHelper->isCatalogEnabled()) {
            return false;
        }

        $this->catalogImages->start();

        return true;
    }
}
