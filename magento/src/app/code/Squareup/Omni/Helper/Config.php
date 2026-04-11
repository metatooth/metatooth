<?php
/**
 * SquareUp
 *
 * Config Helper
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\LocationFactory;
use Squareup\Omni\Model\ResourceModel\Config\CollectionFactory as ConfigCollection;
use Squareup\Omni\Model\ResourceModel\Config as ConfigResource;
use Squareup\Omni\Model\ConfigFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Squareup\Omni\Model\ResourceModel\Location\CollectionFactory as LocationCollection;
use Squareup\Omni\Model\ResourceModel\Inventory\CollectionFactory as InventoryCollection;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Model\ProductFactory;

/**
 * Class Config
 */
class Config extends AbstractHelper
{
    /**
     * @var LocationFactory
     */
    private $locationFactory;

    /**
     * @var ConfigCollection
     */
    private $configCollection;

    /**
     * @var ConfigResource
     */
    private $configResource;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var LocationCollection
     */
    private $locationCollection;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var InventoryCollection
     */
    private $inventoryCollection;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Config constructor.
     * @param LocationFactory $locationFactory
     * @param LocationCollection $locationCollection
     * @param StockRegistryInterface $stockRegistry
     * @param ConfigResource $configResource
     * @param ConfigFactory $configFactory
     * @param ProductFactory $productFactory
     * @param InventoryCollection $inventoryCollection
     * @param Context $context
     */
    public function __construct(
        LocationFactory $locationFactory,
        LocationCollection $locationCollection,
        StockRegistryInterface $stockRegistry,
        ConfigCollection $configCollection,
        ConfigResource $configResource,
        ConfigFactory $configFactory,
        ProductFactory $productFactory,
        InventoryCollection $inventoryCollection,
        Context $context,
        Logger $logger
    ) {
        $this->locationFactory = $locationFactory;
        $this->configCollection = $configCollection;
        $this->configResource = $configResource;
        $this->configFactory = $configFactory;
        $this->locationCollection = $locationCollection;
        $this->stockRegistry = $stockRegistry;
        $this->productFactory = $productFactory;
        $this->inventoryCollection = $inventoryCollection;
        parent::__construct($context);
        $this->logger = $logger;
    }

    /**
     * Save config value to storage
     *
     * @param $path
     * @param $value
     * @param string $scope
     * @param int $scopeId
     */
    public function setConfig($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $config = $this->configFactory->create();

        $this->configResource->load($config, $path, 'path');
        $config->setPath($path);
        $config->setScope($scope);
        $config->setScopeId($scopeId);
        $config->setValue($value);

        try {
            $this->configResource->save($config);
        } catch (\Exception $exception) {
            $this->_logger->error($exception->getMessage());
        }
    }

    /**
     * Retrieve config value by path and scope
     *
     * @param string $path
     *
     * @return mixed
     */
    public function getConfig($path)
    {
        $config = $this->configFactory->create();
        $this->configResource->load($config, $path, 'path');

        return $config->getValue();
    }

    /**
     * Get application id
     *
     * @param bool|null $sandbox
     *
     * @return mixed
     */
    public function getApplicationId($sandbox = null)
    {
        if ($sandbox) {
            return $this->getConfig('squareup_omni/general/sandbox_application_id');
        }

        return $this->getConfig('squareup_omni/general/application_id');
    }

    /**
     * Get application secret
     *
     * @param bool|null $sandbox
     *
     * @return mixed
     */
    public function getApplicationSecret($sandbox = null)
    {
        if ($sandbox) {
            return $this->getConfig('squareup_omni/general/sandbox_application_secret');
        }

        return $this->getConfig('squareup_omni/general/application_secret');
    }

    /**
     * Get location id
     *
     * @param string $scope
     * @param int $scopeId
     *
     * @return string|null
     */
    public function getLocationId($scope = 'default', $scopeId = 0)
    {
        $path = 'squareup_omni/general/location_id';

        if ($this->isSandbox()) {
            $path = 'squareup_omni/general/sandbox_application_location';
        }

        $collection = $this->configCollection->create()
            ->addFieldToFilter('path', ['eq' => $path])
            ->addFieldToFilter('scope', ['eq' => $scope])
            ->addFieldToFilter('scope_id', ['eq' => $scopeId]);

        if ($collection->count()) {
            return $collection->getFirstItem()->getValue();
        }

        return null;
    }

    /**
     * @param $locationId
     */
    public function setLocationId($locationId)
    {
        $path = 'squareup_omni/general/location_id';

        if ($this->isSandbox()) {
            $path = 'squareup_omni/general/sandbox_application_location';
        }

        $collection = $this->configCollection->create()
            ->addFieldToFilter('path', ['eq' => $path])
            ->addFieldToFilter('scope', ['eq' => 'default'])
            ->addFieldToFilter('scope_id', ['eq' => 0]);

        if ($collection->count()) {
            $config = $collection->getFirstItem();
        } else {
            $config = $this->configFactory->create();
        }

        $config->setPath($path);
        $config->setScope('default');
        $config->setScopeId(0);
        $config->setValue($locationId);

        try {
            $this->configResource->save($config);
        } catch (\Exception $exception) {
            $this->_logger->error($exception->getMessage());
        }
    }

    /**
     * Get location currency
     *
     * @return mixed
     */
    public function getLocationCurrency()
    {
        $locationId = $this->getLocationId();
        $location = $this->locationFactory->create()->load($locationId, 'square_id');
        $currency = null;

        if ($location && $location->getId()) {
            $currency = $location->getCurrency();
        }

        return $currency;
    }

    /**
     * Get payment action
     *
     * @return mixed
     */
    public function getPaymentAction()
    {
        return $this->scopeConfig->getValue('payment/squareup_payment/payment_action');
    }

    /**
     * Get OAuth token
     *
     * @param null $trans
     *
     * @return mixed
     */
    public function getOAuthToken($trans = null)
    {
        if ($trans) {
            return (!$this->isSandbox()) ?
                $this->getConfig('squareup_omni/oauth_settings/oauth_token') :
                $this->getApplicationSecret(true);
        }

        return (!$this->isSandbox()) ?
            $this->getConfig('squareup_omni/oauth_settings/oauth_token') :
            $this->getApplicationSecret(true);
    }

    /**
     * Get OAuth expire
     *
     * @return mixed
     */
    public function getOAuthExpire()
    {
        return $this->getConfig('squareup_omni/oauth_settings/oauth_expire');
    }

    /**
     * Check customer sync
     *
     * @return bool
     */
    public function getAllowCustomerSync()
    {
        return $this->getConfig('squareup_omni/customer/customer_sync');
    }

    /**
     * Check order import transactions
     *
     * @return bool
     */
    public function getAllowImportTrans()
    {
        return $this->getConfig('squareup_omni/orders/import_trans');
    }

    /**
     * Check order create sync
     *
     * @return bool
     */
    public function getAllowOrdersSync()
    {
        return $this->getConfig('squareup_omni/orders/create_order');
    }

    /**
     * Get system of records
     *
     * @return mixed
     */
    public function getSor()
    {
        return $this->getConfig('squareup_omni/catalog/sor');
    }

    /**
     * Check catalog sync
     *
     * @return bool
     */
    public function isCatalogEnabled()
    {
        return ($this->getConfig('squareup_omni/catalog/enable_catalog') === '1') ? true : false;
    }

    /**
     * Check inventory sync
     *
     * @return bool
     */
    public function isInventoryEnabled()
    {
        return ($this->getConfig('squareup_omni/catalog/enable_inventory') === '1') ? true : false;
    }

    /**
     * Get application mode
     *
     * @return mixed
     */
    public function getApplicationMode()
    {
        return $this->getConfig('squareup_omni/general/application_mode');
    }

    /**
     * Get old location id
     *
     * @return mixed
     */
    public function getOldLocationId()
    {
        return $this->getConfig('squareup_omni/general/location_id_old');
    }

    /**
     * Check if sandbox mode is enabled
     *
     * @return bool
     */
    public function isSandbox()
    {
        return ($this->getConfig('squareup_omni/general/application_mode') === 'sandbox') ? true : false;
    }

    /**
     * Get cron ranat
     *
     * @return mixed
     */
    public function cronRanAt()
    {
        return $this->getConfig('squareup_omni/general/cron_ran_at');
    }

    /**
     * Get transactions begin time
     *
     * @return mixed
     */
    public function getTransactionsBeginTime()
    {
        return $this->getConfig('squareup_omni/transactions/begin_time');
    }

    /**
     * Set the last cron run for the transactions
     * @param $date
     */
    public function setTransactionsBeginTime($date = null)
    {
        return $this->setConfig('squareup_omni/transactions/begin_time', $date);
    }

    /**
     * Set the last cron run for the refunds
     * @param $date
     */
    public function setRefundsBeginTime($date = null)
    {
        return $this->setConfig('squareup_omni/refunds/begin_time', $date);
    }

    /**
     * Get refunds begin time
     *
     * @return mixed
     */
    public function getRefundsBeginTime()
    {
        return $this->getConfig('squareup_omni/refunds/begin_time');
    }

    /**
     * Save the catalog ran at time
     *
     * @param null $ranAt
     */
    public function saveRanAt($ranAt = null)
    {
        return $this->setConfig('squareup_omni/general/cron_ran_at', $ranAt);
    }

    /**
     * Save the OAuth token received from Square
     *
     * @param null $token
     */
    public function saveOauthToken($token = null)
    {
        return $this->setConfig('squareup_omni/oauth_settings/oauth_token', $token);
    }

    /**
     * Save the OAuth token expiration received from Square
     *
     * @param null $expire
     */
    public function saveOauthExpire($expire = null)
    {
        return $this->setConfig('squareup_omni/oauth_settings/oauth_expire', $expire);
    }

    /**
     * Save the OAuth refresh token received from Square
     *
     * @param null $token
     */
    public function saveOauthRefresh($token = null)
    {
        return $this->setConfig('squareup_omni/oauth_settings/oauth_refresh', $token);
    }

    /**
     * Check if catalog images sync is enabled
     *
     * @return bool
     */
    public function isImagesEnabled()
    {
        return ($this->getConfig('squareup_omni/catalog/enable_images') === '1') ? true : false;
    }

    /**
     * Save the catalog images ran at time
     *
     * @param null $ranAt
     */
    public function saveImagesRanAt($ranAt = null)
    {
        return $this->setConfig('squareup_omni/general/images_ran_at', $ranAt);
    }

    /**
     * Get the catalog images ran at time
     *
     * @return mixed
     */
    public function getImagesRanAt()
    {
        return $this->getConfig('squareup_omni/general/images_ran_at');
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function syncLocationInventory()
    {
        if ($locationId = $this->getLocationId()) {
            $inventory = $this->inventoryCollection->create()
                ->addFieldToFilter('location_id', ['eq' => $locationId]);

            foreach ($inventory as $item) {
                $product = $this->productFactory->create()->load($item->getProductId());
                $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
                $stockItem->setQty($item->getQuantity());
                $stockItem->setIsInStock((int)($item->getQuantity() > 0));
                $stockItem->save();
            }
        }
    }

    /**
     * Get the webhook signature saved in Magento
     *
     * @return mixed
     */
    public function getWebhookSignature()
    {
        return $this->getConfig('squareup_omni/webhooks_settings/webhook_signature');
    }

    /**
     * Check if order conversion is enabled.
     *
     * @return bool
     */
    public function isConvertTransactionsEnabled()
    {
        return $this->getConfig('squareup_omni/orders/convert_transactions');
    }

    /**
     * Check if gift card is enabled.
     *
     * @return bool
     */
    public function isGiftCardEnabled()
    {
        return (bool)$this->getConfig('squareup_omni/orders/enable_gift');
    }

    /**
     * Get the oauth refresh for newer versions of api
     *
     * @return mixed
     */
    public function getRefreshToken()
    {
        return $this->getConfig('squareup_omni/oauth_settings/oauth_refresh');
    }

    /**
     * Check if only the payment method is used
     *
     * @return bool
     */
    public function isPaymentOnly()
    {
        $isCustomerEnabled = (bool)$this->getAllowCustomerSync();
        $isCatalogEnabled = (bool)$this->isCatalogEnabled();
        $isTransactionEnabled = (bool)$this->getAllowImportTrans();
        if($isCustomerEnabled || $isCatalogEnabled ||  $isTransactionEnabled ) {
            return false;
        } else {
            return true;
        }
    }
}
