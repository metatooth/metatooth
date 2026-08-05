<?php
declare(strict_types=1);

namespace Metatooth\SquareCheckout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    private const XML_ACTIVE = 'payment/squarecheckout/active';
    private const XML_SANDBOX = 'payment/squarecheckout/sandbox';
    private const XML_TOKEN_PROD = 'payment/squarecheckout/access_token';
    private const XML_TOKEN_SAND = 'payment/squarecheckout/sandbox_access_token';
    private const XML_LOCATION = 'payment/squarecheckout/location_id';

    public function isActive(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ACTIVE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isSandbox(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_SANDBOX, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getAccessToken(?int $storeId = null): string
    {
        $path = $this->isSandbox($storeId) ? self::XML_TOKEN_SAND : self::XML_TOKEN_PROD;
        return (string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getLocationId(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_LOCATION, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
