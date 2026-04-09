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

namespace Squareup\Omni\Model\Catalog;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\Square;
use Squareup\Omni\Helper\Mapping;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Framework\Model\ResourceModel\Iterator;
use SquareConnect\Api\CatalogApi;
use SquareConnect\Model\BatchRetrieveCatalogObjectsRequest;
use Magento\Catalog\Model\ProductFactory;
use SquareConnect\Model\BatchUpsertCatalogObjectsRequest;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use SquareConnect\Model\BatchDeleteCatalogObjectsRequest;
use Magento\Indexer\Model\Indexer;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Class Export
 */
class Export extends Square
{
    /**
     * @var array
     */
    private $prepProducts = [];

    /**
     * @var array
     */
    private $newProducts = [];

    /**
     * @var array
     */
    private $existingProducts = [];

    /**
     * @var array
     */
    private $addToUpdate = [];

    /**
     * @var array
     */
    private $mIdWithVersion = [];

    /**
     * @var array
     */
    private $mIdWithVersionVar = [];

    /**
     * @var array
     */
    private $squareVersions = [];

    /**
     * @var array
     */
    private $forDeletion = [];

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
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * Export constructor
     *
     * @param ProductCollection $productCollection
     * @param Iterator $iterator
     * @param ProductFactory $productFactory
     * @param ProductResource $productResource
     * @param Indexer $indexer
     * @param DateTime $dateTime
     * @param ProductMetadataInterface $productMetadata
     * @param Config $config
     * @param Logger $logger
     * @param Data $helper
     * @param Mapping $mapping
     * @param Context $context
     * @param Registry $registry
     * @param null $resource
     * @param null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ProductCollection $productCollection,
        Iterator $iterator,
        ProductFactory $productFactory,
        ProductResource $productResource,
        Indexer $indexer,
        DateTime $dateTime,
        ProductMetadataInterface $productMetadata,
        Config $config,
        Logger $logger,
        Data $helper,
        Mapping $mapping,
        Context $context,
        Registry $registry,
        $resource = null,
        $resourceCollection = null,
        array $data = []
    ) {
        $this->productCollection = $productCollection;
        $this->iterator = $iterator;
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
        $this->indexer = $indexer;
        $this->dateTime = $dateTime;
        $this->productMetadata = $productMetadata;
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
     * Start product export
     *
     * @return bool
     */
    public function start()
    {
        $this->logger->info('Start catalog export');
        $ranAt = $this->dateTime->gmtDate('Y-m-d H:i:s');
        $this->productsExport();
        $this->helper->saveRanAt($ranAt);
        $this->logger->info('End catalog export');
        return true;
    }

    /**
     * Export products
     *
     * @return bool
     */
    public function productsExport()
    {
        $this->exportNew();
        $this->exportExisting();
        if (!empty($this->existingProducts)) {
            $this->getVersions();
            $this->addToUpdatePrep();
            $this->addVersions();
        }

        $toSend = [];
        $toSendCount = 0;

        foreach ($this->newProducts as $catalogObject) {
            $noVariations = count($catalogObject['item_data']['variations']);
            $noObjects = $noVariations + 1;

            if ($toSendCount < 500) {
                $toSendCount += $noObjects;
                $toSend[] = $catalogObject;
            } else {
                $this->sendData($toSend);
                $toSendCount = 0;
                $toSend = [];
            }
        }

        if (!empty($toSend)) {
            $this->sendData($toSend);
            $toSendCount = 0;
            $toSend = [];
        }

        $toSendExisting = [];
        $toSendExistingCount = 0;

        foreach ($this->existingProducts as $eCatalogObject) {
            $noEVariations = count($eCatalogObject['item_data']['variations']);
            $noEObjects = $noEVariations + 1;

            if ($toSendExistingCount < 500) {
                $toSendExistingCount += $noEObjects;
                $toSendExisting[] = $eCatalogObject;
            } else {
                $this->sendData($toSendExisting);
                $toSendExistingCount = 0;
                $toSendExisting = [];
            }
        }

        if (!empty($toSendExisting)) {
            $this->sendData($toSendExisting);
            $toSendExistingCount = 0;
            $toSendExisting = [];
        }

        $this->deleteDuplicateFromSquare();
        $this->indexer->load('catalog_category_product')->reindexAll();

        return true;
    }

    /**
     * Export new products
     */
    public function exportNew()
    {
        $collection = $this->productCollection->create()
            ->addAttributeToSelect(
                [
                    'name',
                    'square_id',
                    'category_ids',
                    'price',
                    'sku',
                    'short_description',
                    'image',
                    'square_variation_id'
                ],
                'left'
            )
            ->addAttributeToFilter('square_id', ['null' => true]);

        $this->iterator->walk(
            $collection->getSelect(),
            [[$this, 'processProduct']],
            ['type' => 'new']
        );
    }

    /**
     * Export existing products
     */
    public function exportExisting()
    {
        $ranAt = $this->configHelper->cronRanAt();
        if (null === $ranAt) {
            $ranAt = 1;
        }

        $fromDate = $this->dateTime->gmtDate('Y-m-d H:i:s', $ranAt);
        $toDate = $this->dateTime->gmtDate('Y-m-d H:i:s');

        $collection = $this->productCollection->create()
            ->addAttributeToSelect(
                [
                    'name',
                    'square_id',
                    'category_ids',
                    'price',
                    'sku',
                    'short_description',
                    'image',
                    'square_variation_id'
                ],
                'left'
            )
            ->addAttributeToFilter('updated_at', ['from' => $fromDate, 'to' => $toDate])
            ->addAttributeToFilter('square_id', ['notnull' => true]);

        $this->iterator->walk(
            $collection->getSelect(),
            [[$this, 'processProduct']],
            ['type' => 'existing']
        );
    }

    /**
     * Get product versions
     *
     * @return array|bool
     */
    public function getVersions()
    {
        $versions = [];
        $apiClient = $this->helper->getClientApi();
        $catalogApi = new CatalogApi($apiClient);
        $objectList = [
            "object_ids" => array_keys($this->mIdWithVersion)
        ];
        $objectIds = new BatchRetrieveCatalogObjectsRequest($objectList);

        try {
            // Retrieve the objects
            $apiResponse = $catalogApi->BatchRetrieveCatalogObjects($objectIds);
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        if (null !== $apiResponse->getErrors()) {
            $this->logger->error('There was a error while requesting batch retrieve objects' . __FILE__ . __LINE__);
            return false;
        }

        if (null === $apiResponse->getObjects()) {
            return $versions;
        }

        foreach ($apiResponse->getObjects() as $object) {
            if ($object->getType() == 'ITEM_VARIATION') {
                $this->squareVersions[$object->getId()] = $object->getVersion();
            } else {
                $this->squareVersions[$object->getId()] = $object->getVersion();
                if (null === $object->getItemData()->getVariations()) {
                    continue;
                }

                foreach ($object->getItemData()->getVariations() as $variation) {
                    $this->squareVersions[$variation->getId()] = $variation->getVersion();
                }
            }
        }

        return $versions;
    }

    /**
     * Add product version
     */
    public function addVersions()
    {
        foreach ($this->squareVersions as $squareId => $squareV) {
            if (array_key_exists($squareId, $this->mIdWithVersion)) {
                $mId = $this->mIdWithVersion[$squareId];
                $this->existingProducts[$mId]['version'] = $squareV;
                foreach ($this->existingProducts[$mId]['item_data']['variations'] as &$variation) {
                    if (array_key_exists($variation['id'], $this->squareVersions)) {
                        $variation['version'] = $this->squareVersions[$variation['id']];
                    }
                }
            }
        }
    }

    /**
     * Process product
     *
     * @param $args
     */
    public function processProduct($args)
    {
        if ($args['row']['type_id'] == \Magento\Bundle\Model\Product\Type::TYPE_CODE
            || $args['row']['type_id'] == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            return;
        }

        $parentIds = $this->mapping->isChild($args['row']['entity_id']);
        if ($args['type'] == 'new' && $parentIds !== false) {
            return;
        }

        if ($args['type'] == 'existing' && $parentIds !== false) {
            if ($args['row']['square_id'] != $args['row']['square_variation_id']) {
                $this->forDeletion[] = $args['row']['square_id'];
            }

            $this->addToUpdate[] = $parentIds;
            return;
        }

        $property = $args['type'] . 'Products';
        $product = $this->productFactory->create();
        $product->setData($args['row']);
        $this->{$property}[$args['row']['entity_id']] = $this->mapping->setCatalogObject($product);
        if ($args['type'] === 'existing') {
            $this->mIdWithVersion[$args['row']['square_id']] = $args['row']['entity_id'];
            $this->mIdWithVersionVar[$args['row']['square_variation_id']] = $args['row']['entity_id'];
        }

        $product = null;
    }

    /**
     * Batch export products
     *
     * @param $products
     *
     * @return bool|\SquareConnect\Model\BatchUpsertCatalogObjectsResponse
     */
    public function doBatchCall($products)
    {
        $apiClient = $this->helper->getClientApi();
        $catalogApi = new CatalogApi($apiClient);
        $catalogObjectBatchArr = [
            "idempotency_key" => uniqid(),
            "batches" => [
                [
                    "objects" => array_values($products)
                ]
            ]
        ];

        $catalogObjectBatch = new BatchUpsertCatalogObjectsRequest($catalogObjectBatchArr);

        try {
            $apiResponse = $catalogApi->BatchUpsertCatalogObjects($catalogObjectBatch);
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        if (null !== $apiResponse->getErrors()) {
            $this->logger->error(
                'There was an error in the response, when calling UpsertCatalogObject' . __FILE__ . __LINE__
            );
            return false;
        }

        return $apiResponse;
    }

    /**
     * Save batch ids in magento
     *
     * @param $idMappings
     *
     * @return bool
     */
    public function saveBatchIdsInMagento($idMappings)
    {
        $ids = [];
        $varIds = [];
        foreach ($idMappings as $map) {
            if (stripos($map->getClientObjectId(), "::") !== false) {
                $idWithoutSharp = str_replace("#", "", $map->getClientObjectId());
                $mId = str_replace("::", "", $idWithoutSharp);
                $varIds[$map->getObjectId()] = $mId;
            } else {
                $mId = str_replace("#", "", $map->getClientObjectId());
                $ids[$map->getObjectId()] = $mId;
            }
        }

        foreach ($ids as $sKey => $sId) {
            $product = $this->productFactory->create();

            if ('Enterprise' == $this->productMetadata->getEdition()) {
                $product->setRowId($sId);
            } else {
                $product->setId($sId);
            }

            $product->setStoreId(0);
            $product->setSquareId($sKey);

            try {
                $this->productResource->saveAttribute($product, 'square_id');
            } catch (\Exception $e) {
                $this->logger->error($e->__toString());
            }
        }

        foreach ($varIds as $vKey => $vId) {
            $isChild = $this->mapping->isChild($vId);
            $product = $this->productFactory->create();
            if ('Enterprise' == $this->productMetadata->getEdition()) {
                $product->setRowId($vId);
            } else {
                $product->setId($vId);
            }
            $product->setStoreId(0);
            if ($isChild !== false) {
                $product->setSquareId($vKey);
            }

            $product->setSquareVariationId($vKey);

            try {
                if ($isChild !== false) {
                    $this->productResource->saveAttribute($product, 'square_id');
                }

                $this->productResource->saveAttribute($product, 'square_variation_id');
            } catch (\Exception $e) {
                $this->logger->error($e->__toString());
            }
        }

        return true;
    }

    /**
     * Add to update prep
     */
    public function addToUpdatePrep()
    {
        foreach ($this->addToUpdate as $item) {
            foreach ($item as $_item) {
                if (array_key_exists($_item, $this->existingProducts)) {
                    continue;
                }

                $product = $this->productFactory->create()->load($_item);
                $this->existingProducts[$product->getId()] = $this->mapping->setCatalogObject($product);
                $product = null;
            }
        }
    }

    /**
     * Delete duplicate products from square
     *
     * @param null $ids
     *
     * @return bool
     */
    public function deleteDuplicateFromSquare($ids = null)
    {
        if (empty($this->forDeletion) && null == $ids) {
            return true;
        }

        $apiClient = $this->helper->getClientApi();
        $catalogApi = new CatalogApi($apiClient);
        $deleteBatch = new BatchDeleteCatalogObjectsRequest();
        if (null == $ids) {
            $deleteBatch->setObjectIds($this->forDeletion);
        } else {
            $deleteBatch->setObjectIds($ids);
        }

        try {
            $apiResponse = $catalogApi->batchDeleteCatalogObjects($deleteBatch);
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        if (null !== $apiResponse->getErrors()) {
            $this->logger->error(
                'There was an error in the response, when calling UpsertCatalogObject' . __FILE__ . __LINE__
            );
            return false;
        }

        return true;
    }

    public function sendData($toSend)
    {
        $newApiResponse = $this->doBatchCall($toSend);
        if (false === $newApiResponse) {
            return false;
        }

        $idMappings = $newApiResponse->getIdMappings();
        if ($idMappings) {
            $this->saveBatchIdsInMagento($idMappings);
        }

        return true;
    }
}
