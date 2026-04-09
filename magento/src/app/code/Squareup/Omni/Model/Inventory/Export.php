<?php
/**
 * SquareUp
 *
 * Export Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\Inventory;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\Square;
use Squareup\Omni\Helper\Mapping;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Model\ResourceModel\Iterator;
use Squareup\Omni\Model\ResourceModel\Inventory\CollectionFactory as InventoryCollection;
use Squareup\Omni\Model\InventoryFactory as InventoryFactory;

/**
 * Class Export
 */
class Export extends Square
{
    /**
     * @var array
     */
    private $stock = [];

    /**
     * @var array
     */
    private $products;

    /**
     * @var string
     */
    private $location;

    /**
     * @var int
     */
    private $qty;

    /**
     * @var ProductResource\CollectionFactory
     */
    private $productCollection;

    /**
     * @var Iterator
     */
    private $iterator;

    /**
     * @var ProductResource\CollectionFactory
     */
    private $inventoryCollection;
    /**
     * @var InventoryFactory
     */
    private $inventoryFactory;

    /**
     * Export constructor
     *
     * @param ProductResource\CollectionFactory $collectionFactory
     * @param InventoryCollection $inventoryCollection
     * @param Iterator $iterator
     * @param Config $config
     * @param Logger $logger
     * @param Data $helper
     * @param Mapping $mapping
     * @param Context $context
     * @param Registry $registry
     * @param InventoryFactory $inventoryFactory
     * @param null $resource
     * @param null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ProductResource\CollectionFactory $collectionFactory,
        InventoryCollection $inventoryCollection,
        Iterator $iterator,
        Config $config,
        Logger $logger,
        Data $helper,
        Mapping $mapping,
        Context $context,
        Registry $registry,
        InventoryFactory $inventoryFactory,
        $resource = null,
        $resourceCollection = null,
        array $data = []
    ) {
        $this->productCollection = $collectionFactory;
        $this->iterator = $iterator;
        $this->inventoryCollection = $inventoryCollection;
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
        $this->inventoryFactory = $inventoryFactory;
    }

    /**
     * Start process
     *
     * @param null $products
     * @param null $location
     * @param null $qty
     *
     * @return bool
     */
    public function start($products = null, $location = null, $qty = null)
    {
        $this->products = $products;
        $this->location = $location;
        $this->qty = $qty;
        $this->inventoryExport();
        $this->batchCall();
        return true;
    }

    /**
     * Inventory export
     */
    public function inventoryExport()
    {
        $this->getStockItems();
    }

    /**
     * Get stock items
     */
    public function getStockItems()
    {
        $collection = $this->productCollection->create()
            ->addAttributeToSelect(
                [
                    'sku',
                    'square_id',
                    'square_variation_id'
                ],
                'left'
            )
            ->addAttributeToSelect('stock_status_index.qty')
            ->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            );

        if ($this->products) {
            $collection->addAttributeToFilter('entity_id', ['in' => $this->products]);
        }

        $this->iterator->walk(
            $collection->getSelect(),
            [[$this, 'processStock']]
        );
    }

    /**
     * Process stock
     *
     * @param $args
     */
    public function processStock($args)
    {
        if ($args['row']['type_id'] == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return;
        }

        $inventoryResult = $this->buildInventory($args['row']);

        if ($inventoryResult) {
            $this->stock[] = $inventoryResult;
        }
    }

    /**
     * Batch call
     *
     * @return bool|\SquareConnect\Model\BatchChangeInventoryResponse
     */
    public function batchCall()
    {
        $apiClient = $this->helper->getClientApi();
        $inventoryApi = new \SquareConnect\Api\InventoryApi($apiClient);

        $chunks = array_chunk(array_values($this->stock), 99, true);

        try {
            foreach ($chunks as $chunk) {
                $inventoryObjectBatch = new \SquareConnect\Model\BatchChangeInventoryRequest();
                $inventoryObjectBatch->setIdempotencyKey(uniqid());
                $inventoryObjectBatch->setChanges(array_values($chunk));

                $apiResponse = $inventoryApi->batchChangeInventory($inventoryObjectBatch);
                if (!$apiResponse->getErrors() && ($items = $apiResponse->getCounts())) {
                    $item = array_shift($items);

                    if ($this->products === null && ($productId = $this->_registry->registry('square_product'))) {
                        $this->products[] = $productId;
                        $this->_registry->unregister('square_product');
                    }

                    if ($this->products) {
                        foreach ($this->products as $productId) {
                            $product = $this->productCollection->create()
                                ->getItemById($productId);

                            if ($product->getTypeId() === Configurable::TYPE_CODE) {
                                continue;
                            }

                            $inventoryItem = $this->inventoryCollection->create()
                                ->addFieldToFilter('product_id', ['in' => $this->products])
                                ->addFieldToFilter('location_id', ['eq' => $item->getLocationId()])
                                ->getFirstItem();
                            $inventoryItem->addData([
                                // for configurable products, this contains the configurable product id
                                // but should contain the variation id
                                'product_id' => $productId,
                                'location_id' => $this->location ? $this->location : $item->getLocationId(),
                                'status' => $item->getStatus(),
                                'quantity' => $item->getQuantity(),
                                'calculated_at' => $item->getCalculatedAt(),
                                'received_at' => $item->getCalculatedAt(),
                            ])->save();
                        }
                    } else {
                        $this->batchUpdateInventory();
                    }
                }

                if (null !== $apiResponse->getErrors()) {
                    $this->logger->error(
                        'There was an error in the response, when calling UpsertCatalogObject' . __FILE__ . __LINE__
                    );
                    return false;
                }
            }

            return true;
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return false;
        }
    }

    /**
     * Build inventory
     *
     * @param $product
     *
     * @return bool|\SquareConnect\Model\InventoryChange
     */
    public function buildInventory($product)
    {
        if (empty($product['square_variation_id'])) {
            return false;
        }

        $location_id = $this->location ? $this->location : $this->configHelper->getLocationId();

        $physicalInventory = [
            "reference_id" => $product['entity_id'],
            "catalog_object_id" => $product['square_variation_id'],
            "status" => 'IN_STOCK',
            "location_id" => $location_id,
            "quantity" => (string)round($this->qty ? $this->qty : $product['qty'], 0),
            "occurred_at" => date('Y-m-d\TH:i:s\Z')
        ];

        $physicalCount = new \SquareConnect\Model\InventoryPhysicalCount($physicalInventory);
        $inventory = new \SquareConnect\Model\InventoryChange();
        $inventory->setType('PHYSICAL_COUNT');
        $inventory->setPhysicalCount($physicalCount);

        return $inventory;
    }

    /**
     * Set stock array
     *
     * @param $stock
     */
    public function setStockArr($stock)
    {
        $this->stock = $stock;
    }

    private function batchUpdateInventory()
    {
        $data = [];
        foreach ($this->stock as $stockItem) {
            $count = $stockItem->getPhysicalCount();

            $data[$count->getReferenceId()] = [
                'product_id' => $count->getReferenceId(),
                'location_id' => $count->getLocationId(),
                'status' => $count->getStatus(),
                'quantity' => $count->getQuantity(),
                'calculated_at' => $count->getOccurredAt(),
                'received_at' => $count->getOccurredAt(),
            ];
        }

        $products = $this->inventoryCollection->create()
            ->addFieldToFilter('product_id', ['in' => array_keys($data)])
            ->addFieldToFilter('location_id', ['eq' => $this->configHelper->getLocationId()]);

        foreach ($products as $product) {
            if (isset($data[$product->getProductId()])) {
                $product->addData($data[$product->getProductId()])->save();
                unset($data[$product->getProductId()]);
            }
        }

        if (!empty($data)) {
            foreach ($data as $inventoryItem) {
                $inventory = $this->inventoryFactory->create();
                $inventory->addData($inventoryItem)->save();
            }
        }
    }
}
