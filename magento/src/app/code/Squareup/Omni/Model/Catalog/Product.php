<?php
/**
 * SquareUp
 *
 * Product Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\Catalog;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\Catalog;
use Squareup\Omni\Model\Square;
use Squareup\Omni\Helper\Mapping;
use SquareConnect\Api\CatalogApi;
use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Squareup\Omni\Model\Inventory\Export as InventoryExport;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Model\ResourceModel\Iterator;
use Squareup\Omni\Model\ResourceModel\Inventory\CollectionFactory as InventoryCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Squareup\Omni\Model\InventoryFactory;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use \Magento\CatalogInventory\Model\Stock\ItemFactory as StockItem;

/**
 * Class Product
 */
class Product extends Square
{
    /**
     * @var CatalogApi
     */
    private $catalogApi;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var Export
     */
    private $export;

    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @var InventoryExport
     */
    private $inventoryExport;

    /**
     * @var CollectionFactory
     */
    private $productCollection;

    /**
     * @var Iterator
     */
    private $iterator;

    /**
     * @var InventoryCollection
     */
    private $inventoryCollection;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var InventoryFactory
     */
    private $inventoryFactory;

    /**
     * @var StockItem
     */
    private $stockItem;

    /**
     * Product constructor
     *
     * @param ProductFactory $productFactory
     * @param Export $export
     * @param Configurable $configurable
     * @param InventoryExport $inventoryExport
     * @param CollectionFactory $collectionFactory
     * @param Iterator $iterator
     * @param InventoryCollection $inventoryCollection
     * @param DateTime $dateTime
     * @param InventoryFactory $inventoryFactory
     * @param StockItem $stockItem
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
        ProductFactory $productFactory,
        Export $export,
        Configurable $configurable,
        InventoryExport $inventoryExport,
        CollectionFactory $collectionFactory,
        Iterator $iterator,
        InventoryCollection $inventoryCollection,
        DateTime $dateTime,
        InventoryFactory $inventoryFactory,
        StockItem $stockItem,
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
        $this->productFactory = $productFactory;
        $this->export = $export;
        $this->configurable = $configurable;
        $this->inventoryExport = $inventoryExport;
        $this->productCollection = $collectionFactory;
        $this->iterator = $iterator;
        $this->inventoryCollection = $inventoryCollection;
        $this->dateTime = $dateTime;
        $this->inventoryFactory = $inventoryFactory;
        $this->stockItem = $stockItem;
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
     * Model construct that should be used for object initialization
     */
    public function _construct()
    {
        $this->init();
        $apiClient = $this->helper->getClientApi();
        $this->catalogApi = new CatalogApi($apiClient);
    }

    /**
     * Create product
     *
     * @param $product
     *
     * @return $this
     */
    public function createProduct($product)
    {
        $isChildOfConfig = $this->isChild($product);
        if ($isChildOfConfig !== false) {
            foreach ($isChildOfConfig as $configId) {
                $configProduct = $this->productFactory->create()->load($configId);
                $this->updateProduct($configProduct);
            }

            return $this;
        }

        $forDeletion = [];
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $forDeletion = $this->prepDelete($product, $forDeletion);
        }

        $idemPotency = uniqid();
        $catalogObjectArr = [
            "idempotency_key" => $idemPotency,
            "object" => $this->mapping->setCatalogObject($product, null)
        ];

        $this->logger->info(json_encode($catalogObjectArr));
        $catalogObject = new \SquareConnect\Model\UpsertCatalogObjectRequest($catalogObjectArr);

        try {
            $apiResponse = $this->catalogApi->UpsertCatalogObject($catalogObject);
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return $this;
        }

        if (null !== $apiResponse->getErrors()) {
            $this->logger->error(
                'There was an error in the response, when calling UpsertCatalogObject' . __FILE__ . __LINE__
            );
            return $this;
        }

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            if (null !== $apiResponse->getIdMappings()) {
                $this->mapping->saveSquareIdsInMagento($apiResponse->getIdMappings());
            }
        }

        $this->saveIdsInMagento($apiResponse, $product);
        $this->doInventory($product);

        if (!empty($forDeletion)) {
            $this->export->deleteDuplicateFromSquare($forDeletion);
        }

        return $this;
    }

    /**
     * Update product
     *
     * @param $product
     *
     * @return $this
     */
    public function updateProduct($product)
    {
        $isChildOfConfig = $this->isChild($product);
        if ($isChildOfConfig !== false) {
            foreach ($isChildOfConfig as $configId) {
                $configProduct = $this->productFactory->create()->load($configId);
                $this->updateProduct($configProduct);
            }

            return $this;
        }

        $forDeletion = [];
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $forDeletion = $this->prepDelete($product, $forDeletion);
        }

        try {
            $receivedObj = $this->catalogApi->retrieveCatalogObject($product->getSquareId(), true);
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return $this;
        }

        $idemPotency = uniqid();
        $catalogObjectArr = [
            "idempotency_key" => $idemPotency,
            "object" => $this->mapping->setCatalogObject($product, $receivedObj)
        ];

        $this->logger->info(json_encode($catalogObjectArr));
        $catalogObject = new \SquareConnect\Model\UpsertCatalogObjectRequest($catalogObjectArr);

        try {
            $apiResponse = $this->catalogApi->UpsertCatalogObject($catalogObject);
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return $this;
        }

        if (null !== $apiResponse->getErrors()) {
            $this->logger->error(
                'There was an error in the response, when calling UpsertCatalogObject' . __FILE__ . __LINE__
            );
            return $this;
        }

        if (null !== $apiResponse->getIdMappings()) {
            $this->mapping->saveSquareIdsInMagento($apiResponse->getIdMappings());
        }

        $this->doInventory($product);

        if (!empty($forDeletion)) {
            $this->export->deleteDuplicateFromSquare($forDeletion);
        }

        return $this;
    }

    /**
     * Save ids in magento
     *
     * @param $apiResponse
     * @param $product
     *
     * @return bool
     */
    public function saveIdsInMagento($apiResponse, $product)
    {
        $idMappings = $apiResponse->getIdMappings();

        $ids = [];
        $varIds = [];
        foreach ($idMappings as $map) {
            if (stripos($map->getClientObjectId(), "::") !== false) {
                $idWithoutSharp = str_replace("#", "", $map->getClientObjectId());
                $id = str_replace("::", "", $idWithoutSharp);
                $varIds[$id][] = $map->getObjectId();
            } else {
                $ids[str_replace("#", "", $map->getClientObjectId())] = $map->getObjectId();
            }
        }

        $product->setSquareId($ids[$product->getId()]);
        $product->setRowId($product->getRowId());

        if (isset($varIds[$product->getId()])) {
            $product->setSquareVariationId(implode(":", $varIds[$product->getId()]));
        }

        try {
            $product->getResource()->saveAttribute($product, 'square_id');
            if (isset($varIds[$product->getId()])) {
                $product->getResource()->saveAttribute($product, 'square_variation_id');
            }
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        return true;
    }

    /**
     * Check if product is child
     *
     * @param $product
     *
     * @return array|bool
     */
    public function isChild($product)
    {
        $parentIds = $this->configurable->getParentIdsByChild($product->getId());
        return (!empty($parentIds))? $parentIds : false;
    }

    /**
     * Do inventory
     *
     * @param $product
     *
     * @return bool
     */
    public function doInventory($product)
    {
        if (false === $this->configHelper->isInventoryEnabled()) {
            return true;
        }

        $stockArrays = [];
        $inventory = $this->inventoryExport;
        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $collection = $this->configurable->getUsedProductCollection($product)
                ->addAttributeToSelect('*')
                ->joinField(
                    'qty',
                    'cataloginventory_stock_item',
                    'qty',
                    'product_id=entity_id',
                    '{{table}}.stock_id=1',
                    'left'
                )
                ->addFilterByRequiredOptions();

            foreach ($collection as $aProduct) {
                $stockValues = [
                    "entity_id" => $aProduct->getId(),
                    "square_variation_id" => $aProduct->getSquareVariationId(),
                    "qty" => $aProduct->getQty()
                ];
                $stockArr = $inventory->buildInventory($stockValues);
                $stockArrays[] = $stockArr;
            }
        } else {
            $stockItem = $this->stockItem->create()->load($product->getId(), 'product_id');
            $qty = $stockItem->getQty();
            $stockValues = [
                "entity_id" => $product->getId(),
                "square_variation_id" => $product->getSquareVariationId(),
                "qty" => $qty
            ];
            $stockArr = $inventory->buildInventory($stockValues);
            $stockArrays[] = $stockArr;

            $inventoryCollection = $this->inventoryCollection->create()
                ->addFieldToFilter('product_id', ['eq' => $product->getId()])
                ->addFieldToFilter('location_id', ['eq' => $this->configHelper->getLocationId()]);

            if ($inventoryCollection->count()) {
                foreach ($inventoryCollection as $item) {
                    $item->setQuantity($qty);
                    $item->save();
                }
            } else {
                $inventoryItem = $this->inventoryFactory->create();
                $inventoryItem->setData(
                    [
                        'product_id' => $product->getId(),
                        'location_id' => $this->configHelper->getLocationId(),
                        'status' => $qty > 0 ? 'IN_STOCK' : 'OUT_OF_STOCK',
                        'quantity' => $qty,
                        'calculated_at' => $this->dateTime->gmtDate(),
                        'received_at' => $this->dateTime->gmtDate(),
                    ]
                )->save();
            }
        }

        if (empty($stockArrays)) {
            $this->logger->info('No stock to update');
            return true;
        }

        try {
            $inventory->setStockArr($stockArrays);
            $inventory->batchCall();
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        return true;
    }

    /**
     * Prepare product for delete
     *
     * @param $product
     * @param $forDeletion
     *
     * @return array
     */
    private function prepDelete($product, $forDeletion)
    {
        $collection = $this->configurable->getUsedProductCollection($product)
            ->addAttributeToSelect('*')
            ->addFilterByRequiredOptions();
        foreach ($collection as $aProduct) {
            if ($aProduct->getSquareId() != $aProduct->getSquareVariationId()) {
                $forDeletion[] = $aProduct->getSquareId();
            }
        }

        return $forDeletion;
    }

    /**
     * get existing square ids
     *
     * @return array
     */
    public function getExistingSquareIds()
    {
        $idsArray = [];
        $collection = $this->productCollection->create()
            ->addAttributeToSelect(['square_id'])
            ->addAttributeToFilter('square_id', ['notnull' => true]);

        $this->iterator->walk(
            $collection->getSelect(),
            [[$this, 'processSquareIds']],
            ['idsArray' => &$idsArray]
        );

        return $idsArray;
    }

    /**
     * Process square ids
     *
     * @param $args
     *
     * @return mixed
     */
    public function processSquareIds($args)
    {
        $args['idsArray'][$args['row']['entity_id']] = $args['row']['square_id'];
        return $args;
    }

    /**
     * Reset children ids
     *
     * @param $product
     *
     * @return array
     */
    public function resetChildrenIds($product)
    {
        $ids = [];
        $collection = $this->configurable
            ->getUsedProductCollection($product)
            ->addAttributeToSelect('*')
            ->addFilterByRequiredOptions();
        foreach ($collection as $aProduct) {
            $ids[] = $aProduct->getId();
        }

        return $ids;
    }
}
