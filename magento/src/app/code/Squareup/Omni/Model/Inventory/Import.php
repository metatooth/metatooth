<?php
/**
 * SquareUp
 *
 * Import Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\Inventory;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use SquareConnect\Model\BatchRetrieveInventoryCountsRequest;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\Square;
use Squareup\Omni\Helper\Mapping;
use Magento\Framework\Model\ResourceModel\Iterator;
use Squareup\Omni\Model\ResourceModel\Inventory as InventoryResource;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Model\StockRegistry;
use Squareup\Omni\Model\Location;
use Squareup\Omni\Logger\Debugger;

/**
 * Class Import
 */
class Import extends Square
{
    /**
     * @var array
     */
    private $variationIds = [];

    /**
     * @var array
     */
    private $counts = [];

    /**
     * @var InventoryResource
     */
    private $inventoryResource;

    /**
     * @var StockItemRepository
     */
    private $stockItemRepository;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ProductCollection
     */
    private $productCollection;

    /**
     * @var Iterator
     */
    private $iterator;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var StockRegistry
     */
    private $stockRegistry;

    /**
     * @var Location
     */
    private $location;
    /**
     * @var Debugger
     */
    private $debugger;

    /**
     * Import constructor
     *
     * @param InventoryResource $inventoryResource
     * @param StockItemRepository $stockItemRepository
     * @param DateTime $dateTime
     * @param ProductCollection $productCollection
     * @param Iterator $iterator
     * @param ProductFactory $productFactory
     * @param StockRegistry $stockRegistry
     * @param Location $location
     * @param Config $config
     * @param Logger $logger
     * @param Data $helper
     * @param Mapping $mapping
     * @param Context $context
     * @param Registry $registry
     * @param Debugger $debugger
     * @param null $resource
     * @param null $resourceCollection
     * @param array $data
     */
    public function __construct(
        InventoryResource $inventoryResource,
        StockItemRepository $stockItemRepository,
        DateTime $dateTime,
        ProductCollection $productCollection,
        Iterator $iterator,
        ProductFactory $productFactory,
        StockRegistry $stockRegistry,
        Location $location,
        Config $config,
        Logger $logger,
        Data $helper,
        Mapping $mapping,
        Context $context,
        Registry $registry,
        Debugger $debugger,
        $resource = null,
        $resourceCollection = null,
        array $data = []
    ) {
        $this->inventoryResource = $inventoryResource;
        $this->stockItemRepository = $stockItemRepository;
        $this->dateTime = $dateTime;
        $this->productCollection = $productCollection;
        $this->iterator = $iterator;
        $this->productFactory = $productFactory;
        $this->stockRegistry = $stockRegistry;
        $this->location = $location;
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
        $this->debugger = $debugger;
    }

    /**
     * Start process
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function start()
    {
        $this->logger->info('Start Inventory Import');
        $this->inventoryImport();
        $this->logger->info('End Inventory Import');
        return true;
    }

    /**
     * Inventory import
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function inventoryImport()
    {
        $this->getVariationsIds();
        $apiResponse = $this->batchCall();
        if (false === $apiResponse) {
            return false;
        }

        $this->processStock();

        return true;
    }

    /**
     * Process stock
     *
     * @param $apiResponse
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processStock()
    {
        foreach ($this->counts as $item) {
            if (!array_key_exists($item->getCatalogObjectId(), $this->variationIds)) {
                continue;
            }

            if ($item->getStatus() !== 'IN_STOCK') {
                continue;
            }

            $sInventory = $this->inventoryResource
                ->loadByProductIdAndLocationId(
                    $this->variationIds[$item->getCatalogObjectId()],
                    $item->getLocationId()
                );

            if ($sInventory === null) {
                continue;
            }

            /*$stripDate = str_replace("T", ' ', $item->getCalculatedAt());
            $stripDate = str_replace("Z", '', $stripDate);*/
            $stripDate = date("Y-m-d H:i:s", strtotime($item->getCalculatedAt()));

            $this->debugger->info(sprintf("Local inventory record calculated_at: %s", $sInventory->getCalculatedAt()));
            $this->debugger->info(sprintf("Square inventory record calculated_at: %s", $stripDate));
            if ($sInventory->getCalculatedAt() != $stripDate) {
                $sInventory->setStatus($item->getStatus());
                $sInventory->setQuantity($item->getQuantity());
                $sInventory->setCalculatedAt($item->getCalculatedAt());
                $sInventory->setReceivedAt($this->dateTime->gmtDate());
                if (null === $sInventory->getId()) {
                    $sInventory->setProductId($this->variationIds[$item->getCatalogObjectId()]);
                    $sInventory->setLocationId($item->getLocationId());
                    $data["location_id"] = $item->getLocationId();
                    $this->debugger->info(sprintf('Create inventory record for product with id %s', $this->variationIds[$item->getCatalogObjectId()]));
                }else{
                    $this->debugger->info(sprintf('Update inventory record for product with id %s', $this->variationIds[$item->getCatalogObjectId()]));
                }

                try {
                    $sInventory->save();
                    $this->logger->info(
                        'Inventory saved for location: #' . $sInventory->getLocationId() .
                        'for product: #' . $sInventory->getProductId()
                    );
                } catch (\Exception $e) {
                    $this->logger->error($e->__toString());
                }
            }

            if ($sInventory->getLocationId() != $this->configHelper->getLocationId()) {
                continue;
            }

            $stockItem = $this->stockRegistry->getStockItem($this->variationIds[$item->getCatalogObjectId()]);
            $stock = $this->stockItemRepository->get($stockItem->getItemId());

            if ($stock->getQty() == $item->getQuantity()) {
                continue;
            }

            $stock->setData('manage_stock', 1);
            $stock->setData('is_in_stock', 1);
            $stock->setData('use_config_notify_stock_qty', 0);
            $stock->setQty($item->getQuantity());

            try {
                $stock->save();
                $this->logger->info('Inventory updated for: #' . $stock->getProductId());
            } catch (\Exception $e) {
                $this->logger->error($e->__toString());
            }
        }
    }

    /**
     * Get variations ids
     */
    public function getVariationsIds()
    {
        $collection = $this->productCollection->create()
            ->addAttributeToSelect('square_variation_id')
            ->addAttributeToFilter('square_variation_id', ['notnull' => true]);

        $this->iterator->walk(
            $collection->getSelect(),
            [[$this, 'processVariation']]
        );
    }

    /**
     * Batch call
     *
     * @return bool|\SquareConnect\Model\BatchRetrieveInventoryCountsResponse
     */
    public function batchCall()
    {
        $apiClient = $this->helper->getClientApi();
        $inventoryApi = new \SquareConnect\Api\InventoryApi($apiClient);
        $chunks = array_chunk(array_keys($this->variationIds), 999);

        foreach($chunks as $chunk){
            $cursor = null;

            do {
                $inventoryObjectBatchArr = array(
                    "catalog_object_ids" => $chunk,
                );

                $inventoryObjectBatch = new BatchRetrieveInventoryCountsRequest($inventoryObjectBatchArr);
                $inventoryObjectBatch->setCursor($cursor);

                try{
                    $apiResponse = $inventoryApi->batchRetrieveInventoryCounts($inventoryObjectBatch);
                } catch (\SquareConnect\ApiException $e) {
                    $this->logger->error($e->__toString());
                    return false;
                }

                if (null !== $apiResponse->getErrors()) {
                    $this->logger->error(
                        'There was an error in the response, when calling batchRetrieveInventoryCounts' .
                        __FILE__ . __LINE__
                    );
                    return false;
                }

                $counts = [];
                if($apiResponse->getCounts() !== null){
                    $counts = $apiResponse->getCounts();
                }
                $this->counts = array_merge($this->counts, $counts);
                $cursor = $apiResponse->getCursor();
            } while ($cursor);
        }

        return true;
    }

    /**
     * Process product variation
     *
     * @param $args
     */
    public function processVariation($args)
    {
        $this->variationIds[$args['row']['square_variation_id']] = $args['row']['entity_id'];
    }
}
