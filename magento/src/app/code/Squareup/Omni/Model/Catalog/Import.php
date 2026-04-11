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

namespace Squareup\Omni\Model\Catalog;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\Square;
use Squareup\Omni\Helper\Mapping;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\ConfigurableFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Squareup\Omni\Model\Catalog\Product as SquareProduct;
use Squareup\Omni\Model\ResourceModel\Product as SquareProductResource;
use Squareup\Omni\Model\ResourceModel\Inventory\CollectionFactory as InventoryCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Squareup\Omni\Model\ResourceModel\Location\CollectionFactory as LocationCollection;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Store\Model\StoreManagerInterface;
use Squareup\Omni\Logger\Debugger;

/**
 * Class Import
 */
class Import extends Square
{
    /**
     * @var
     */
    private $confAttrId;

    /**
     * @var int
     */
    private $entityTypeId = 4;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $receivedIds = [];

    /**
     * @var array
     */
    private $objects = [];

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var ConfigurableFactory
     */
    private $configurableFactory;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Product
     */
    private $catalogProduct;

    /**
     * @var SquareProductResource
     */
    private $squareProductResource;

    private $locations = [];

    private $date;

    private $locationCollection;

    private $inventoryCollection;

    private $messageManager;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Debugger
     */
    private $debugger;

    /**
     * Import constructor
     *
     * @param ProductResource $productResource
     * @param ConfigurableFactory $configurableFactory
     * @param ProductFactory $productFactory
     * @param EavConfig $eavConfig
     * @param DirectoryList $directoryList
     * @param Product $catalogProduct
     * @param SquareProductResource $squareProductResource
     * @param Config $config
     * @param Logger $logger
     * @param Data $helper
     * @param Mapping $mapping
     * @param DateTime $dateTime
     * @param LocationCollection $locationCollection
     * @param InventoryCollection $inventoryCollection
     * @param MessageManager $messageManager
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param Debugger $debugger
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        ProductResource $productResource,
        ConfigurableFactory $configurableFactory,
        ProductFactory $productFactory,
        EavConfig $eavConfig,
        DirectoryList $directoryList,
        SquareProduct $catalogProduct,
        SquareProductResource $squareProductResource,
        Config $config,
        Logger $logger,
        Data $helper,
        Mapping $mapping,
        DateTime $dateTime,
        LocationCollection $locationCollection,
        InventoryCollection $inventoryCollection,
        MessageManager $messageManager,
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        Debugger $debugger,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->productResource = $productResource;
        $this->configurableFactory = $configurableFactory;
        $this->productFactory = $productFactory;
        $this->eavConfig = $eavConfig;
        $this->directoryList = $directoryList;
        $this->catalogProduct = $catalogProduct;
        $this->squareProductResource = $squareProductResource;
        $this->date = $dateTime;
        $this->locationCollection = $locationCollection;
        $this->inventoryCollection = $inventoryCollection;
        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;

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
     * Model construct that should be used for object initialization
     */
    public function _construct()
    {
        $this->init();
        $this->entityTypeId = $this->productResource->getTypeId();
        $this->setConfAttrId();
    }

    /**
     * Start process
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function start()
    {
        $this->logger->info('Start catalog import');
        $this->getProducts();
        //$this->deleteProducts();
        $this->logger->info('End catalog import');
        return true;
    }

    /**
     * Get products
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProducts()
    {
        $locations = $this->locationCollection->create()
            ->addFieldToFilter('status', ['eq' => 1]);
        foreach ($locations as $location) {
            $this->locations[] = $location->getSquareId();
        }

        $this->storeManager->setCurrentStore(0);
        $this->callSquare();

        return true;
    }

    /**
     * Update product
     *
     * @param $item
     * @param $type
     * @param $id
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateProduct($item, $type, $id)
    {
        $product = $this->productFactory->create()->load($id);
        if ($product->getTypeId() != $type) {
            $this->changeProduct($item, $type, $product->getId());
            return true;
        }

        $product = $this->prepUpdateProduct($item, $type, $product);

        if (!$product) {
            return false;
        }

        try {
            if ($product) {
                $this->debugger->info(sprintf('Update product with id %s', $product->getId()));
                $product = $product->save();
                $this->receivedIds[] = $item->getId();
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        if ($type == 'simple') {
            $this->addLocations($item, $id, true);
            return true;
        }

        foreach ($item->getItemData()->getVariations() as $key => $variation) {
            // need to update or create
            $idExists = $this->productExists($variation->getId());

            if ($idExists !== false) {
                $simpleProduct = $this->productFactory->create()->load($idExists);
                $childProduct = $this->prepUpdateProduct($variation, 'simple', $simpleProduct, $key);

                if (!$childProduct) {
                    continue;
                }

                $this->debugger->info(sprintf('Update product (variation) with id %s', $product->getId()));
                $this->saveProduct($childProduct);
                $this->addLocations($variation, $childProduct->getId(), true);
                $this->receivedIds[] = $variation->getId();
            } else {
                $childIds = $product->getTypeInstance()->getUsedProductIds($product);
                $childProduct = $this->prepProduct($variation, 'simple', $product->getId(), $key);

                if (!$childProduct) {
                    continue;
                }

                $childId = $this->saveProduct($childProduct);

                if ($childId !== false) {
                    $childIds[] = $childId->getId();
                    $this->receivedIds[] = $variation->getId();
                }

                $this->addLocations($variation, $childProduct->getId());
                $this->configurableFactory->create()->saveProducts($product, $childIds);
            }
        }

        return true;
    }

    public function changeProduct($item, $type, $id)
    {
        $this->deleteProduct($id);
        $this->createProduct($item, $type);
        return true;
    }

    /**
     * Remove inventory locations
     *
     * @param $locations
     * @param $id
     */
    public function cleanLocations($locations, $id)
    {
        $collection = $this->locationCollection->create()
            ->addFieldToFilter('status', ['neq' => 1]);

        foreach ($collection as $item) {
            $locations[] = $item->getSquareId();
        }

        $locations = $this->inventoryCollection->create()
            ->addFieldToFilter('product_id', ['eq' => $id])
            ->addFieldToFilter('location_id', ['in' => $locations]);

        try {
            foreach ($locations as $location) {
                $this->debugger->info(sprintf("Clean product inventory for location with id %s for product with id %s", $location->getId(), $id));
                $location->delete();
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->__toString());
        }
    }

    /**
     * Add inventory locations
     *
     * @param $item
     * @param $id
     * @param bool $flag
     */
    public function addLocations($item, $id, $flag = false)
    {
        $productLocations = [];

        if ($flag) {
            $locations = $this->inventoryCollection->create()
                ->addFieldToFilter('product_id', ['eq' => $id]);

            foreach ($locations as $location) {
                $productLocations[] = $location->getLocationId();
            }
        }

        if ($item->getPresentAtAllLocations()) {
            if ($item->getAbsentAtLocationIds()) {
                $locations = array_diff($this->locations, $item->getAbsentAtLocationIds(), $productLocations);
            } else {
                $locations = array_diff($this->locations, $productLocations);
            }
        } else {
            $importedLocation = array_intersect($this->locations, $item->getPresentAtLocationIds() ? $item->getPresentAtLocationIds() : array());
            $locations = array_diff($importedLocation, $productLocations);
        }

        foreach ($locations as $location) {
            $data = [
                'product_id' => $id,
                'location_id' => $location,
                'status' => '',
                'quantity' => 0,
                'calculated_at' => $this->date->gmtDate(),
                'received_at' => $this->date->gmtDate()
            ];
            $inventory = $this->inventoryCollection->create()
                ->addFieldToFilter('product_id', ['eq' => $id])
                ->addFieldToFilter('location_id', ['eq' => $location])
                ->getFirstItem();

            $this->debugger->info(sprintf("Save product inventory for location with id %s for product %s", $location, $id));

            $inventory->addData($data)->save();
        }

        $unavailableLocations = $this->inventoryCollection->create()
            ->addFieldToFilter('product_id', ['eq' => $id])
            ->addFieldToFilter('location_id', ['nin' => $locations]);

        foreach ($unavailableLocations as $location) {
            if ($location->getLocationId() === $this->configHelper->getLocationId()) {
                /**
                 * @var \Magento\Catalog\Model\Product $product
                 */
                $product = $this->productFactory->create()->load($location->getProductId());
                $product->setStockData([
                    'qty' => 0,
                    'is_in_stock' => false
                ])->save();
            }

            $this->debugger->info(sprintf("Delete product inventory for location with id %s for product %s", $location->getId(), $id));

            $location->delete();
        }

        $this->cleanLocations($item->getAbsentAtLocationIds(), $id);
    }

    /**
     * Create product
     *
     * @param $item
     * @param $type
     * @param null $id
     *
     * @return bool
     */
    public function createProduct($item, $type, $id = null)
    {
        $product = $this->prepProduct($item, $type);
        if (false === $product) {
            return false;
        }

        try {
            $product = $product->save();


            $this->debugger->info(sprintf('Created fromCreate product with id %s', $product->getId()));
            $this->receivedIds[] = $item->getId();
            $this->logger->info(
                'Product with squareId: #' . $item->getId(). ' was created in magento with #'
                . $product->getId()
            );
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        if ($type == 'simple') {
            $this->addLocations($item, $product->getId());
            return true;
        }

        $childIds = [];
        $childData = [];
        $confPrice = 0;
        foreach ($item->getItemData()->getVariations() as $key => $variation) {
            $itemVariationData = $variation->getItemVariationData();
            if ($key == 0) {
                $price = $itemVariationData->getPriceMoney();
                $confPrice = $this->helper->transformAmount($price ? $price->getAmount() : 0);
            }

            $childProduct = $this->prepProduct($variation, 'simple', $product->getId(), $key);

            if (!$childProduct) {
                continue;
            }

            $child = $this->saveProduct($childProduct);
            $this->debugger->info(sprintf('Create product (variation) with id %s', $child->getId()));
            if ($child !== false) {
                $this->addLocations($variation, $child->getId());
                $childIds[] = $child->getId();
                $childData[] = [
                    'id' => $child->getId(),
                    'value_id' => $child->getSquareVariation(),
                    'label' => $itemVariationData->getName(),
                    'price' => $child->getPrice()
                ];

                $this->receivedIds[] = $variation->getId();
            }
        }

        $this->configurableFactory->create()->saveProducts($product, $childIds);
        $this->saveConfigurableData($product, $childData, $confPrice);

        return true;
    }

    /**
     * Prepare product
     *
     * @param $data
     * @param $type
     * @param bool $parentId
     * @param bool $vKey
     *
     * @return bool|\Magento\Catalog\Model\Product
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepProduct($data, $type, $parentId = false, $vKey = false)
    {
        if ($vKey === false) {
            $vKey = 0;
        }

        $itemData = $data->getItemData();
        if ($type == 'simple') {
            if (($variationData = $data->getItemVariationData()) && !$variationData->getSku()) {
                return false;
            } elseif (($simpleData = $data->getItemData()) &&
                !$simpleData->getVariations()[0]->getItemVariationData()->getSku()) {
                return false;
            }

            if ($parentId !== false) {
                $itemVariation = $data;
                $itemData = $data->getItemVariationData();
                $itemVariationData = $data->getItemVariationData();
                $sku = $itemVariationData->getSku();
                $description = 'Child product';
            } else {
                $itemVariation = $itemData->getVariations()[$vKey];
                $itemVariationData = $itemData->getVariations()[$vKey]->getItemVariationData();
                $sku = $itemVariationData->getSku();
                $description = $itemData->getDescription();
            }
        } else {
            $itemVariation = $itemData->getVariations()[0];
            $itemVariationData = $itemData->getVariations()[0]->getItemVariationData();
            $sku = $data->getId();
            $description = $itemData->getDescription();
        }

        if (null === $sku) {
            $this->logger->error('Product: ' . $itemData->getName() . ' does not have any sku');
            $this->errors[] = $itemData->getName() . ' no sku';
            return false;
        }

        $sku = $this->getUniqueSku($sku);
        $product = $this->productFactory->create();
        $price = $itemVariationData->getPriceMoney();
        $inData = [
            'sku' => $sku,
            'url_key' => $sku . '-' . time(),
            'price' => $this->helper->transformAmount($price ? $price->getAmount() : 0),
            'name' => $itemData->getName(),
            'description' => $description,
            'short_description' => $description,
            'weight' => 1,
            'status' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
            'visibility' => ($parentId === false)?
                Visibility::VISIBILITY_BOTH :
                Visibility::VISIBILITY_NOT_VISIBLE,
            'attribute_set_id' => 4,
            'type_id' => $type,
            'tax_class_id' => 2,
            'website_ids' => [1],
            'square_id' => $data->getId(),
            'square_variation_id' => ($type == 'configurable')? '' : $itemVariation->getId(),
            'square_updated_at' => $itemVariation->getUpdatedAt(),
            'stock_data' => [
                'use_config_manage_stock' => 0,
                'manage_stock' => 1,
                'min_sale_qty' => 1,
                'max_sale_qty' => 2,
                'is_in_stock' => 0,
                'qty' => 0
            ]
        ];

        $product->setData($inData);

        /*if ($data->getType() == 'ITEM' && null !== $itemData->getImageUrl()) {
            $imageName = $this->downloadImage($itemData->getImageUrl(), $data->getId());
            if (false !== $imageName) {
                $product->addImageToMediaGallery($imageName, ['image', 'small_image', 'thumbnail'], true, false);
            }
        }*/

        if ($parentId !== false) {
            $product->setSquareVariation($this->getVariationOptionId($product->getName()));
        }

        if ($type == 'configurable') {
            $product->getTypeInstance()->setUsedProductAttributeIds([$this->confAttrId], $product);
            $configurableAttributesData = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);

            $product->setCanSaveConfigurableAttributes(true);
            $product->setConfigurableAttributesData($configurableAttributesData);
        }

        return $product;
    }

    /**
     * Prepare product for update
     *
     * @param $data
     * @param $type
     * @param $product
     * @param bool $vKey
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepUpdateProduct($data, $type, $product, $vKey = false)
    {
        if ($vKey === false) {
            $vKey = 0;
        }

        $itemData = $data->getItemData();
        if ($type == 'simple') {
            if (($variationData = $data->getItemVariationData()) && !$variationData->getSku()) {
                return false;
            } elseif (($simpleData = $data->getItemData()) &&
                !$simpleData->getVariations()[0]->getItemVariationData()->getSku()) {
                return false;
            }
            if ($data->getType() == 'ITEM_VARIATION') {
                $itemData = $data->getItemVariationData();
                $itemVariationData = $data->getItemVariationData();
                $sku = $itemVariationData->getSku();
                $description = 'Child product';
            } else {
                if (empty($itemData->getVariations()[$vKey])) {
                    return false;
                }
                $itemVariationData = $itemData->getVariations()[$vKey]->getItemVariationData();
                $sku = $itemVariationData->getSku();
                $description = $itemData->getDescription();
            }
        } else {
            $itemVariationData = $itemData->getVariations()[0]->getItemVariationData();
            $sku = $data->getId();
            $description = $itemData->getDescription();
        }

        $sku = $this->getUniqueSku($sku, $product->getId());

        $product = $this->productFactory->create()->load($product->getId());
        $inData = [
            'sku' => $sku,
            'name' => $itemData->getName(),
            'description' => $description,
            'short_description' => $description,
            'square_updated_at' => $data->getUpdatedAt()
        ];
        if (!empty($itemVariationData->getPriceMoney())) {
            $inData['price'] = $this->helper->transformAmount($itemVariationData->getPriceMoney()->getAmount());
        }

        $product->addData($inData);

        $parentId = $this->mapping->isChild($product->getId());
        if ($parentId !== false) {
            $product->setSquareVariation($this->getVariationOptionId($product->getName()));
        }

        return $product;
    }

    /**
     * Save product
     *
     * @param $product
     *
     * @return bool
     */
    public function saveProduct($product)
    {
        try {
            $id = $product->save();
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        return $id;
    }

    /**
     * Check if product exists
     *
     * @param $squareId
     *
     * @return string
     */
    public function productExists($squareId)
    {
        return $this->squareProductResource->productExists($squareId);
    }

    /**
     * Set config attribute id
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setConfAttrId()
    {
        $attribute = $this->eavConfig->getAttribute($this->entityTypeId, 'square_variation');
        $this->confAttrId = $attribute->getId();
    }

    /**
     * Save configurable data
     *
     * @param $product
     * @param $data
     * @param $confPrice
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveConfigurableData($product, $data, $confPrice)
    {
        $product = $this->productFactory->create()->load($product->getId());
        $configurableProductsData = $product->getConfigurableProductsData();
        $configurableAttributesData = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);

        foreach ($data as $simple) {
            $productData = [
                'label'         => $simple['label'],
                'attribute_id'  => $this->confAttrId,
                'value_index'   => $simple['value_id'],
                'is_percent'    => 0,
                'pricing_value' => $simple['price'] - $confPrice
            ];

            $configurableProductsData[$simple["id"]] = $productData;
            $configurableAttributesData[0]['values'][] = $productData;
        }

        try {
            $product->setConfigurableProductsData($configurableProductsData);
            $product->setConfigurableAttributesData($configurableAttributesData);
            $product->setCanSaveConfigurableAttributes(true);
            $product->save();
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
        }
    }

    /**
     * Download image
     *
     * @param $image
     * @param $id
     *
     * @return bool|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function downloadImage($image, $id)
    {
        $config = [
            'adapter'   => 'Zend_Http_Client_Adapter_Curl',
            'curloptions' => [CURLOPT_SSL_VERIFYPEER => false],
        ];

        try {
            $client = new \Zend_Http_Client($image, $config);
            $client->setMethod(\Zend_Http_Client::GET);
            $client->setConfig(['timeout' => 60]);
            $response = $client->request();
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        if ($response->getStatus() != 200) {
            $this->logger->error('There was an error when trying to download image: ' . $image);
            return false;
        }

        $fileArr = explode('/', $image);
        $fileName = array_pop($fileArr);
        $extArr = explode('.', $fileName);
        $ext = array_pop($extArr);
        $fullPath = $this->directoryList->getPath('media') . '/square/' . $id . '.' . $ext;
        $file = new \SplFileObject($fullPath, 'w+');
        $file->fwrite($response->getBody());
        $file = null;

        return $fullPath;
    }

    /**
     * Get variation option id
     *
     * @param $label
     *
     * @return bool|null|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getVariationOptionId($label)
    {
        $attr = $this->productResource->getAttribute(\Squareup\Omni\Model\Square::SQUARE_VARIATION_ATTR);
        $optionId = false;
        if ($attr->usesSource()) {
            $optionId = $attr->getSource()->getOptionId($label);
        }

        if (null == $optionId) {
            return $this->addVariationOption($label);
        }

        return $optionId;
    }

    /**
     * Add variation option
     *
     * @param $label
     *
     * @return null|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addVariationOption($label)
    {
        $attribute =$this->productResource->getAttribute(\Squareup\Omni\Model\Square::SQUARE_VARIATION_ATTR);
        $value['option'] = [$label];
        $result = ['value' => $value];
        $attribute->setData('option', $result);
        try {
            $attribute = $attribute->save();
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
        }

        $attribute = $this->eavConfig->getAttribute($this->entityTypeId, $attribute->getId());
        if ($attribute->usesSource()) {
            $optionId = $attribute->getSource()->getOptionId($label);
        }

        return $optionId;
    }

    /**
     * Delete products
     */
    public function deleteProducts()
    {
        $existingArray = $this->catalogProduct->getExistingSquareIds();
        $toDeleteIds = array_diff($existingArray, $this->receivedIds);
        $this->squareProductResource->deleteProducts(array_keys($toDeleteIds));
    }

    /**
     * Call square
     *
     * @return bool
     */
    public function callSquare()
    {
        $client = $this->helper->getClientApi();
        $api = new \SquareConnect\Api\CatalogApi($client);
        $cursor = null;

        $s = 1;
        while ($s != 0) {
            $types = 'ITEM';
            try {
                $apiResponse = $api->listCatalog($cursor, $types);
            } catch (\SquareConnect\ApiException $e) {
                $errors = $e->getResponseBody()->errors;
                $this->logger->error($e->__toString());

                $errorDetail = '';
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        $errorDetail = $error->category;
                    }

                    $this->messageManager->addErrorMessage(
                        __(
                            '%s Make sure you retrieved OAuth Token or you selected a location.',
                            $errorDetail
                        )
                    );
                }

                return false;
            }

            $cursor = $apiResponse->getCursor();

            if (null !== $apiResponse->getErrors()) {
                $this->logger->error('There was an error in the response from Square, to catalog items');
                return false;
            }

            if (null === $apiResponse->getObjects()) {
                $this->logger->error('There are no items square to import');
                return false;
            }

            $ranAt = $this->configHelper->cronRanAt();
            foreach ($apiResponse->getObjects() as $item) {
                $itemVariation = $item->getItemData()->getVariations();
                // need item variation for sku and price
                if (null === $itemVariation) {
                    continue;
                }

                if (in_array($item->getId(), $this->receivedIds)) {
                    continue;
                }

                $type = (!isset($itemVariation[1])) ? 'simple' : 'configurable';
                $productExists = $this->productExists($item->getId());
                if ($productExists !== false) {
                    $squareUpdatedAt = $this->getSquareupUpdatedAt($productExists, $item->getUpdatedAt());
                    if (strtotime($item->getUpdatedAt()) <= strtotime($squareUpdatedAt)) {
                        if ($type != 'configurable') {
                            $this->receivedIds[] = $item->getId();
                            continue;
                        }
                        $noUpdate = true;

                        $tempReceived = [];
                        foreach ($itemVariation as $variation) {
                            $tempReceived[] = $variation->getId();
                            $productExistsV = $this->productExists($variation->getId());
                            $squareUpdatedAtV = $this->getSquareupUpdatedAt($productExistsV, $variation->getUpdatedAt());
                            if ($productExistsV === false || strtotime($variation->getUpdatedAt()) > strtotime($squareUpdatedAtV)) {
                                $noUpdate = false;
                            }
                        }

                        if (true === $noUpdate) {
                            $this->receivedIds[] = $item->getId();
                            $this->receivedIds = array_merge($this->receivedIds, $tempReceived);
                            continue;
                        }
                    }
                }

                $action = ($productExists === false)? 'create' : 'update';
                $method = $action . 'Product';
                $this->{$method}($item, $type, $productExists);
            }

            if ($cursor === null) {
                $s = 0;
            }
        }

        return true;
    }

    /**
     * Get unique sku
     *
     * @param $sku
     *
     * @param $productId
     * @return string
     */
    public function getUniqueSku($sku, $productId = 0)
    {
        $a = 1;
        $newSku = $sku;
        while ($a != 0) {
            $skuExists = $this->squareProductResource->skuExists($newSku);
            if ($skuExists === false || $skuExists === $productId) {
                $a = 0;
            } else {
                $oldProductId = $skuExists;
                $squareId = $this->productResource
                    ->getAttributeRawValue($oldProductId, 'square_id', 0);
                if ($squareId === false || empty($squareId)) {
                    $newProductSku = $this->checkNewSku($newSku);
                    $product = $this->productFactory->create();
                    $product->setId($oldProductId);
                    $product->setSku($newProductSku);
                    try {
                        $product->save();
                    } catch (\Exception $e) {
                        $this->logger->error($e->__toString());
                        return false;
                    }

                    $a++;
                } else {
                    $newSku = $this->checkNewSku($newSku);
                    $a++;
                }
            }
        }

        return $newSku;
    }

    public function checkNewSku($sku)
    {
        $i = 1;
        $newSku = $sku;
        while ($i != 0) {
            $skuExists = $this->squareProductResource->skuExists($newSku);
            if ($skuExists === false) {
                $i = 0;
            } else {
                $newSku = $sku . $i;
                $i++;
            }
        }

        return $newSku;
    }

    public function deleteProduct($id)
    {
        try {
            $this->productFactory->create()->setId($id)->delete();
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        return true;
    }

    private function variationExists($variationId){

        return $this->productFactory->create()->loadByAttribute('square_variation_id', $variationId);
    }

    public function getSquareupUpdatedAt($pId, $itemUpdatedAt)
    {
        $value = $this->productResource->getAttributeRawValue(
            $pId,
            ['square_updated_at'],
            0
        );

        if(empty($value)){
            return $itemUpdatedAt;
        }

        return $value;
    }
}
