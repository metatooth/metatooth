<?php
/**
 * SquareUp
 *
 * Square Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Helper\Mapping;

/**
 * Class Square
 */
class Square extends AbstractModel
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Mapping
     */
    protected $mapping;

    /**
     * Variation attribute
     */
    const SQUARE_VARIATION_ATTR = 'square_variation';

    /**
     * Square constructor
     *
     * @param Config $config
     * @param Logger $logger
     * @param Data $helper
     * @param Mapping $mapping
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
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
        $this->configHelper = $config;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->mapping = $mapping;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Prepare image directory
     */
    public function init()
    {
        $this->helper->prepImageDir();
    }
}
