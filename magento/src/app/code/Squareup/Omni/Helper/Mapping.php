<?php
/**
 * SquareUp
 *
 * Mapping Helper
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Helper;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as TypeConfigurable;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Squareup\Omni\Logger\Logger;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product;
use Squareup\Omni\Model\ResourceModel\Product as SquareProductResource;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Class Mapping
 */
class Mapping extends AbstractHelper
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var Product
     */
    private $productResource;

    private $squareProductResource;

    /**
     * @var array
     */
    private $itemMapping = [
        'name' => 'name',
        'description' => 'description',
        'abbreviation',
        'label_color',
        'available_online', // if not available online fi
        'available_electronically', // type download or virtual
        'tax_ids' => 'tax_class_id',
        'image_url' => 'image',
        'sku' => 'sku',
    ];

    private $column;

    /**
     * Mapping constructor
     *
     * @param Context $context
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param Configurable $configurable
     * @param Data $helper
     * @param Config $configHelper
     * @param ProductFactory $productFactory
     * @param Product $productResource
     */
    public function __construct(
        Context $context,
        Logger $logger,
        StoreManagerInterface $storeManager,
        Configurable $configurable,
        Data $helper,
        Config $configHelper,
        ProductFactory $productFactory,
        Product $productResource,
        SquareProductResource $squareProductResource,
        ProductMetadataInterface $productMetadata
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->configurable = $configurable;
        $this->helper = $helper;
        $this->configHelper = $configHelper;
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
        $this->squareProductResource = $squareProductResource;
        $this->column = 'entity_id';

        if ('Enterprise' == $productMetadata->getEdition()) {
            $this->column = 'row_id';
        }
        parent::__construct($context);
    }

    /**
     * Get mapping
     *
     * @param $type
     *
     * @return mixed
     */
    public function getMapping($type)
    {
        return $this->{'_' . $type . 'Mapping'};
    }

    /**
     * Set catalog object
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \SquareConnect\Model\RetrieveCatalogObjectResponse|null $receivedObj
     *
     * @return array
     */
    public function setCatalogObject($product, $receivedObj = null)
    {
        $version = (null !== $receivedObj) ? $receivedObj->getObject()->getVersion() : null;
        $image = $product->getResource()
            ->getAttributeRawValue($product->getData($this->column), 'square_product_image', 0);

        $catalogObject = [
            "type" => "ITEM",
            "id" => (empty($product->getSquareId())) ? '#' . $product->getId() : $product->getSquareId(),
            "version" => $version,
            "present_at_all_locations" => ($product->getTypeId() === TypeConfigurable::TYPE_CODE) ? true : false,
            "present_at_location_ids" => $this->helper->getProductLocations($product->getId()),
            "absent_at_location_ids" => [],
            "item_data" => [
                "name" => $product->getName(),
                "description" => $product->getShortDescription(),
                "abbreviation" => substr($product->getName(), 0, 2),
                "available_online" => true,
                "available_for_pickup" => false,
                "tax_ids" => (null !== $receivedObj)? $receivedObj->getObject()->getItemData()->getTaxIds() : [],
                "modifier_list_info" => [],
                "available_electronically" => (
                    $product->getTypeId() == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE
                ) ? true : false,
                "image_url" => $image ? $image : '',
                "variations" => $this->setItemVariation($product, $receivedObj)
            ]
        ];

        if(null !== $receivedObj) {
            $catalogObject['item_data']['category_id'] = $receivedObj->getObject()->getItemData()->getCategoryId();
        }

        return $catalogObject;
    }

    /**
     * Set item variation
     *
     * @param $product
     * @param null $receivedObj
     *
     * @return array
     */
    public function setItemVariation($product, $receivedObj = null)
    {
        $variations = [];
        $versions = $this->getVersions($receivedObj);
        $productVariations = [];
        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
            $productVariations[] = $product;
        }

        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL) {
            $productVariations[] = $product;
        }

        if ($product->getTypeId() == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE) {
            $productVariations[] = $product;
        }

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $collection = $this->configurable->getUsedProductCollection($product)
                ->addAttributeToSelect('*')
                ->addFilterByRequiredOptions();
            foreach ($collection as $aProduct) {
                $aProduct->setConfProductId($product->getId());
                $productVariations[] = $aProduct;
            }
        }

        foreach ($productVariations as $productVar) {
            $version = (empty($productVar->getSquareVariationId()))?
                null :
                (array_key_exists($productVar->getSquareVariationId(), $versions))?
                    $versions[$productVar->getSquareVariationId()] :
                    null;
            $id = (empty($productVar->getSquareVariationId()))?
                '#' . $productVar->getId() . '::' :
                $productVar->getSquareVariationId();

            if ($productVar->getSquareVariationId() != $productVar->getSquareId()
                && null !== $productVar->getConfProductId()) {
                $version = null;
                $id = '#' . $productVar->getId() . '::';
            }

            $variation = [
                "id" => $id,
                "type" => 'ITEM_VARIATION',
                "version" => $version,
                "present_at_all_locations" => false,
                "present_at_location_ids" => $this->helper->getProductLocations($productVar->getId()),
                "absent_at_location_ids" => [],
                "item_variation_data" => [
                    "sku" => $productVar->getSku(),
                    "name" => $productVar->getName(),
                    "track_inventory" => true,
                    "pricing_type" => "FIXED_PRICING",
                    "price_money" => [
                        "amount" => $this->helper->processAmount($productVar->getPrice()),
                        "currency" => $this->storeManager->getStore()->getCurrentCurrency()->getCode()
                    ]
                ]
            ];
            $variations[] = $variation;
        }

        return $variations;
    }

    /**
     * Get versions
     *
     * @param $obj
     *
     * @return array
     */
    public function getVersions($obj)
    {
        $versions = [];
        if (null == $obj) {
            return $versions;
        }

        $obj = $obj->getObject();
        $versions[$obj->getId()] = $obj->getVersion();
        if (null !== $obj->getItemData() && null !== $obj->getItemData()->getVariations()) {
            foreach ($obj->getItemData()->getVariations() as $variation) {
                $versions[$variation->getId()] = $variation->getVersion();
            }
        }

        return $versions;
    }

    /**
     * Check if product is child
     *
     * @param $id
     *
     * @return array|bool
     */
    public function isChild($id)
    {
        $parentIds = $this->configurable->getParentIdsByChild($id);
        return (!empty($parentIds))? $parentIds : false;
    }

    /**
     * Save square ids
     *
     * @param $idMappings
     *
     * @return bool
     */
    public function saveSquareIdsInMagento($idMappings)
    {
        $itemIds = [];
        $varIds = [];
        foreach ($idMappings as $map) {
            if (stripos($map->getClientObjectId(), "::") !== false) {
                $idWithoutSharp = str_replace("#", "", $map->getClientObjectId());
                $mId = str_replace("::", "", $idWithoutSharp);
                $varIds[$map->getObjectId()] = $mId;
            } else {
                $mId = str_replace("#", "", $map->getClientObjectId());
                $itemIds[$map->getObjectId()] = $mId;
            }
        }

        foreach ($itemIds as $squareId => $mId) {
            $product = $this->productFactory->create();
            $rowId = $this->squareProductResource->getRowId($product->getResource(), $mId);
            $product->setId($mId);
            $product->setRowId($rowId);

            try {
                $product->setSquareId($map->getObjectId());
                $product->setSquareVariationId($map->getObjectId());
                $this->productResource->saveAttribute($product, 'square_id');
                $this->productResource->saveAttribute($product, 'square_variation_id');
            } catch (\Exception $e) {
                $this->logger->error($e->__toString());
            }
        }

        foreach ($varIds as $squareId => $mId) {
            $isChild = $this->isChild($mId);
            $product = $this->productFactory->create();
            $rowId = $this->squareProductResource->getRowId($product->getResource(), $mId);
            $product->setId($mId);
            $product->setRowId($rowId);

            try {
                if ($isChild !==  false) {
                    $product->setSquareId($squareId);
                }

                $product->setSquareVariationId($squareId);
                if ($isChild !==  false) {
                    $this->productResource->saveAttribute($product, 'square_id');
                }

                $this->productResource->saveAttribute($product, 'square_variation_id');
            } catch (\Exception $e) {
                $this->logger->error($e->__toString());
            }
        }

        return true;
    }
}
