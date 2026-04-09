<?php

namespace Squareup\Omni\Helper;

use Magento\Cron\Model\ScheduleFactory;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\RegionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Cache\Frontend\Pool as CacheFrontendPool;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\ResourceModel\Location\CollectionFactory as LocationCollection;
use Squareup\Omni\Model\ResourceModel\Location as LocationResource;
use Squareup\Omni\Model\ResourceModel\Product as ProductResource;
use Squareup\Omni\Model\ResourceModel\Inventory\CollectionFactory as InventoryCollection;
use Squareup\Omni\Model\ResourceModel\Inventory as InventoryResource;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Squareup\Omni\Model\ResourceModel\Config as ConfigResource;
use Squareup\Omni\Model\ConfigFactory;
use Magento\Framework\App\ProductMetadataInterface;

class Hook extends Data
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    private $configResource;

    private $configFactory;

    private $config;

    private $inventoryResource;

    private $inventoryCollection;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    private $locationResource;

    /**
     * Hook constructor.
     *
     * @param Config $configHelper
     * @param RegionFactory $regionFactory
     * @param DirectoryList $directoryList
     * @param File $file
     * @param LocationCollection $locationCollection
     * @param EavConfig $eavConfig
     * @param ResourceConnection $resourceConnection
     * @param Session $customerSession
     * @param WriterInterface $writer
     * @param DateTime $dateTime
     * @param ProductResource $productResource
     * @param Registry $registry
     * @param Context $context
     * @param CacheTypeList $cacheTypeList
     * @param CacheFrontendPool $cacheFrontendPool
     * @param ScheduleFactory $scheduleFactory
     * @param Logger $log
     * @param StockRegistryInterface $stockRegistry
     * @param ConfigResource $configResource
     * @param ConfigFactory $configFactory
     * @param InventoryResource $inventoryResource
     * @param InventoryCollection $inventoryCollection
     */
    public function __construct(
        Config $configHelper,
        RegionFactory $regionFactory,
        DirectoryList $directoryList,
        File $file,
        LocationCollection $locationCollection,
        EavConfig $eavConfig,
        ResourceConnection $resourceConnection,
        Session $customerSession,
        WriterInterface $writer,
        DateTime $dateTime,
        ProductResource $productResource,
        Registry $registry,
        Context $context,
        CacheTypeList $cacheTypeList,
        CacheFrontendPool $cacheFrontendPool,
        ScheduleFactory $scheduleFactory,
        Logger $log,
        StockRegistryInterface $stockRegistry,
        ConfigResource $configResource,
        ConfigFactory $configFactory,
        InventoryResource $inventoryResource,
        InventoryCollection $inventoryCollection,
        ProductMetadataInterface $productMetadata,
        LocationResource $locationResource
    ) {
        parent::__construct(
            $configHelper,
            $regionFactory,
            $directoryList,
            $file,
            $locationCollection,
            $eavConfig,
            $resourceConnection,
            $customerSession,
            $writer,
            $dateTime,
            $productResource,
            $registry,
            $context,
            $cacheTypeList,
            $cacheFrontendPool,
            $scheduleFactory,
            $log
        );

        $this->stockRegistry = $stockRegistry;
        $this->configResource = $configResource;
        $this->configFactory = $configFactory;
        $this->inventoryResource = $inventoryResource;
        $this->inventoryCollection = $inventoryCollection;
        $this->productMetadata = $productMetadata;
        $this->locationResource = $locationResource;
    }

    /**
     * Get last time the web hook executed.
     *
     * @return mixed
     */
    public function getWebHookTime($locationId)
    {
        return $this->locationResource->getWebhookTimeByLocationId($locationId);
    }

    /**
     * Set last execution time for the web hook.
     *
     * @param null $createdAt
     */
    public function setWebHookTime($locationId = null, $createdAt = null)
    {
        if (null === $createdAt) {
            $createdAt = date(DATE_RFC3339, time());
        }

        $this->locationResource->addWebhookTimeToLocation($locationId, $createdAt);
    }

    public function execute($locationId)
    {
        $this->log->info('Start execute hook');

        $lastUpdate = $this->getWebHookTime($locationId);
        if (null === $lastUpdate) {
            $this->setWebHookTime($locationId);
        }

        $api = new \SquareConnect\Api\InventoryApi($this->getClientApi());
        $request = new \SquareConnect\Model\BatchRetrieveInventoryChangesRequest();
        $request->setUpdatedAfter($lastUpdate);
        $request->setLocationIds([$locationId]);

        try {
            $result = $api->batchRetrieveInventoryChanges($request);
            $changes = $result->getChanges();
            if (!$changes) {
                $this->log->info('No inventory changes');
                return true;
            }

            foreach ($changes as $change) {
                $physicalCount = $change->getPhysicalCount();
                $inventoryAdjustment = $change->getAdjustment();
                if (null === $physicalCount) {
                    $inventoryChange = $inventoryAdjustment;
                } else {
                    if ($physicalCount->getSource()) {
                        continue;
                    }

                    $inventoryChange = $physicalCount;
                }

                $this->updateStock($inventoryChange);
            }
        } catch (\Exception $exception) {
            $this->log->error($exception->getMessage());
        }

        return true;
    }

    /**
     * Update Magento stock.
     *
     * @param \SquareConnect\Model\InventoryPhysicalCount $physicalCount
     */
    private function updateStock($physicalCount)
    {
        $column = 'entity_id';

        if ('Enterprise' == $this->productMetadata->getEdition()) {
            $column = 'row_id';
        }

        $data = $this->productResource->getProductData($physicalCount->getCatalogObjectId());
        if (get_class($physicalCount) === 'SquareConnect\Model\InventoryAdjustment') {
            $toStatus = $physicalCount->getToStatus();
            $locationId = $physicalCount->getToLocationId();
        } else {
            $toStatus = null;
            $locationId = $physicalCount->getLocationId();
        }

        $qty = $physicalCount->getQuantity();

        $localItem = $this->inventoryResource->loadByProductIdAndLocationId($data[$column], $locationId);
        if (null === $localItem) {
            return true;
        }

        if ($localItem->getCreatedAt() >= $physicalCount->getCreatedAt()) {
            return true;
        }

        $oldQty = $localItem->getQuantity();
        $newQty = $qty;
        if (null !== $toStatus) {
            $newQty = ($toStatus == 'IN_STOCK')? $oldQty + $qty : $oldQty - $qty;
        }

        $localItem->setQuantity($newQty);
        $localItem->setCreatedAt($physicalCount->getCreatedAt());

        try {
            $localItem->save();
        } catch (\Exception $exception) {
            $this->log->error($exception->getMessage());
            return false;
        }

        if ($locationId == $this->configHelper->getLocationId()) {
            try {
                $stock = $this->stockRegistry->getStockItemBySku($data['sku']);
                $stock->setData('manage_stock', 1);
                $stock->setData('use_config_notify_stock_qty', 0);
                $stock->setData('is_in_stock', $newQty > 0 ? 1 : 0);
                $stock->setQty($newQty);

                $this->stockRegistry->updateStockItemBySku($data['sku'], $stock);
                $this->log->info('Inventory updated for: #' . $stock->getProductId());
                // Save the created time for changes that were retrieved from square in order to
                // not duplicate changes
            } catch (\Exception $exception) {
                $this->log->error($exception->getMessage());
                return false;
            }
        }

        $this->setWebHookTime($locationId, $physicalCount->getCreatedAt());

        return true;
    }
}
