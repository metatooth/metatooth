<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/31/2018
 * Time: 5:36 PM
 */

namespace Squareup\Omni\Controller\Adminhtml\Inventory;

use Magento\Backend\App\Action;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Squareup\Omni\Model\InventoryFactory;
use Magento\CatalogInventory\Model\Stock\ItemFactory as StockItemFactory;
use Magento\Catalog\Model\ProductFactory;
use Squareup\Omni\Helper\Data as DataHelper;
use Squareup\Omni\Helper\Mapping as MappingHelper;
use Squareup\Omni\Logger\Logger;
use Magento\Framework\Controller\ResultFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

class RemoveSquareInventory extends Action
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var InventoryFactory
     */
    private $inventoryFactory;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var StockItemFactory
     */
    private $stockItemFactory;
    /**
     * @var DataHelper
     */
    private $dataHelper;
    /**
     * @var MappingHelper
     */
    private $mappingHelper;
    /**
     * @var Logger
     */
    private $logger;

    private $configurabile;

    /**
     * RemoveSquareInventory constructor.
     * @param Action\Context $context
     * @param ConfigHelper $configHelper
     * @param InventoryFactory $inventoryFactory
     * @param ProductFactory $productFactory
     * @param StockItemFactory $stockItemFactory
     * @param DataHelper $dataHelper
     * @param MappingHelper $mappingHelper
     * @param Logger $logger
     */
    public function __construct(
        Action\Context $context,
        ConfigHelper $configHelper,
        InventoryFactory $inventoryFactory,
        ProductFactory $productFactory,
        StockItemFactory $stockItemFactory,
        DataHelper $dataHelper,
        MappingHelper $mappingHelper,
        Logger $logger,
        Configurable $configurabile
    ) {
        parent::__construct($context);

        $this->configHelper = $configHelper;
        $this->inventoryFactory = $inventoryFactory;
        $this->productFactory = $productFactory;
        $this->stockItemFactory = $stockItemFactory;
        $this->dataHelper = $dataHelper;
        $this->mappingHelper = $mappingHelper;
        $this->logger = $logger;
        $this->configurabile = $configurabile;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax() || !$this->configHelper->getSor()) { // is magento sor
            return false;
        }

        $params = $this->getRequest()->getParams();

        if (empty($params['locationId']) || empty($params['productId'])) {
            return false;
        }

        $productId = $params['productId'];
        if (isset($params['childProductId']) && !empty($params['childProductId'])) {
            $productId = $params['childProductId'];
        }
        $inventory = $this->inventoryFactory->create()->getCollection()
            ->addFieldToFilter('product_id', ['eq' => $productId])
            ->addFieldToFilter('location_id', ['eq' => $params['locationId']])
            ->getFirstItem();
        $product = $this->productFactory->create()->load($params['productId']);

        if ($inventory->getId()) {
            if ($inventory->getId() === $this->configHelper->getLocationId()) {
                $stockItem = $this->stockItemFactory->create()->load($productId, 'product_id');
                $stockItem->setQty(0)
                    ->setIsInStock(0)
                    ->save();
            }

            $inventory->delete();

            $this->removeLocationFromSquare($product, $productId);

            $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            return $result->setData([]);
        }

        return false;
    }

    private function removeLocationFromSquare($product, $productId)
    {
        $apiClient = $this->dataHelper->getClientApi();
        $catalogApi = new \SquareConnect\Api\CatalogApi($apiClient);

        try {
            // Retrieve the objects
            $receivedObj = $catalogApi->retrieveCatalogObject($product->getSquareId(), true);
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        $idemPotency = uniqid();
        // update $catalogObjectArr['object']['present at location_ids']
        $productLocationIds = $this->inventoryFactory
            ->create()
            ->getCollection()
            ->addFieldToFilter('product_id', $productId)
            ->getColumnValues('location_id');

        if ($this->configurabile->getParentIdsByChild($product->getId())) {
            $object = $receivedObj->getObject();
            $catalogObjectArr = array(
                "idempotency_key" => $idemPotency,
                "object" => [
                    'type' => $object->getType(),
                    'id' => $object->getId(),
                    'version' => $object->getVersion(),
                    'present_at_all_locations' => $object->getPresentAtAllLocations(),
                    'present_at_location_ids' => $object->getPresentAtLocationIds(),
                    'absent_at_location_ids' => array(),
                    'item_variation_data' => $object->getItemVariationData()
                ]
            );
        } else {
            $catalogObjectArr = [
                "idempotency_key" => $idemPotency,
                "object" => $this->mappingHelper->setCatalogObject($product, $receivedObj)
            ];

            foreach ($catalogObjectArr['object']['item_data']['variations'] as &$variation) {
                if ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                    if ($variation['item_variation_data']['sku'] === $this->productFactory->create()
                            ->load($productId)
                            ->getSku()) { // change only selected variation
                        $variation['present_at_location_ids'] = $productLocationIds;
                    }
                } else {
                    $variation['present_at_location_ids'] = $productLocationIds;
                }
            }
        }

        $catalogObjectArr['object']['present_at_location_ids'] = $productLocationIds;
        $catalogObjectRequest = new \SquareConnect\Model\UpsertCatalogObjectRequest($catalogObjectArr);

        try {
            $apiResponse = $catalogApi->upsertCatalogObject($catalogObjectRequest);
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return $this;
        }

        if (null !== $apiResponse->getErrors()) {
            $this->logger->error(
                'There was an error in the response, when calling UpsertCatalogObject' . __FILE__ . __LINE__
            );
        }
    }
}
