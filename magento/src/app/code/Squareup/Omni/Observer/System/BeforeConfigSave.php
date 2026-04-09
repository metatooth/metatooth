<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/15/2018
 * Time: 10:54 AM
 */

namespace Squareup\Omni\Observer\System;

use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;

class BeforeConfigSave implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var \Squareup\Omni\Helper\Config
     */
    private $configHelper;
    /**
     * @var \Squareup\Omni\Logger\Logger
     */
    private $logger;

    /**
     * BeforeConfigSave constructor.
     * @param Registry $registry
     * @param \Squareup\Omni\Helper\Config $configHelper
     * @param \Squareup\Omni\Logger\Logger $logger
     */
    public function __construct(
        Registry $registry,
        \Squareup\Omni\Helper\Config $configHelper,
        \Squareup\Omni\Logger\Logger $logger
    ) {
        $this->registry = $registry;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return BeforeConfigSave
     */
    public function execute(Observer $observer)
    {
        try {
            $this->registry->register('before_app_mode', $this->configHelper->getApplicationMode());
            $this->registry->register('square_application_id', $this->configHelper->getApplicationId());
            $this->registry->register('square_application_secret', $this->configHelper->getApplicationSecret());
            $this->registry->register('before_location_id', $this->configHelper->getLocationId());
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
        }

        return $this;
    }
}
