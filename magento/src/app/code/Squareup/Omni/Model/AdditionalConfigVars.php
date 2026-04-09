<?php

namespace Squareup\Omni\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Squareup\Omni\Helper\Config;

class AdditionalConfigVars implements ConfigProviderInterface
{
    private $configHelper;

    public function __construct(Config $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    public function getConfig()
    {
        $additionalVariables['squareupApplicationId'] = $this->configHelper->getApplicationId($this->configHelper->isSandbox());
        $additionalVariables['squareupLocationId'] = $this->configHelper->getLocationId();
        return $additionalVariables;
    }
}
