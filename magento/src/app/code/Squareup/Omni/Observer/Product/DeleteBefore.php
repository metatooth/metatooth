<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/15/2018
 * Time: 10:43 AM
 */

namespace Squareup\Omni\Observer\Product;

use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Squareup\Omni\Model\ResourceModel\Product as ResourceProduct;
use Squareup\Omni\Model\ResourceModel\Inventory\CollectionFactory as InventoryCollection;
use Magento\Catalog\Model\ProductFactory as ProductFactory;

class DeleteBefore implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Squareup\Omni\Helper\Config
     */
    private $configHelper;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $product;
    /**
     * @var \Squareup\Omni\Helper\Date
     */
    private $helper;
    /**
     * @var \Squareup\Omni\Logger\Logger
     */
    private $logger;

    /**
     * @var Registry
     */
    private $registry;

    private $inventoryCollection;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var ResourceProduct
     */
    private $resourceProduct;

    /**
     * SaveAfter constructor.
     * @param \Squareup\Omni\Helper\Config $configHelper
     * @param \Squareup\Omni\Helper\Data $helper
     * @param \Squareup\Omni\Model\Catalog\Product $product
     * @param \Squareup\Omni\Logger\Logger $logger
     * @param Registry $registry
     * @param InventoryCollection $inventoryCollection
     * @param ProductFactory $productFactory
     * @param ResourceProduct $resourceProduct
     */
    public function __construct(
        \Squareup\Omni\Helper\Config $configHelper,
        \Squareup\Omni\Helper\Data $helper,
        \Squareup\Omni\Model\Catalog\Product $product,
        \Squareup\Omni\Logger\Logger $logger,
        Registry $registry,
        InventoryCollection $inventoryCollection,
        ProductFactory $productFactory,
        ResourceProduct $resourceProduct
    ) {
        $this->configHelper = $configHelper;
        $this->product = $product;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->inventoryCollection = $inventoryCollection;
        $this->productFactory = $productFactory;
        $this->resourceProduct = $resourceProduct;
    }

    /**
     * @param Observer $observer
     * @return DeleteAfter
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $product = $this->productFactory->create()->load($product->getId());
        $this->clearInventory($product->getId());

        if ($this->configHelper->getSor() == \Squareup\Omni\Model\System\Config\Source\Options\Records::SQUARE) {
            $this->registry->unregister('delete_product_squareid');
            return $this;
        }

        if (false === $this->configHelper->isCatalogEnabled()) {
            $this->registry->unregister('delete_product_squareid');
            return $this;
        }

        if (!$squareId = $this->registry->registry('delete_product_squareid')) {
            return $this;
        }
        $squareId = $product->getSquareId();
        if (null === $squareId) {
            return $this;
        }

        $this->registry->unregister('delete_product_squareid');

        $apiClient = $this->helper->getClientApi();
        $catalogApi = new \SquareConnect\Api\CatalogApi($apiClient);

        try {
            $apiResponse = $catalogApi->DeleteCatalogObject($squareId);
            if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $childrenIdsJson = $this->registry->registry('delete_product_' . $product->getId());
                $childrenIds = json_decode($childrenIdsJson);
                if (!empty($childrenIds)) {
                    $this->resourceProduct->resetProducts($childrenIds);
                    $this->registry->unregister('delete_product_' . $product->getId());
                }
            }
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return $this;
        }

        if (null !== $apiResponse->getErrors()) {
            $this->logger->error(
                'There was an error in the response, when calling UpsertCatalogObject' . __FILE__ . __LINE__
            );
        }

        return $this;
    }

    private function clearInventory($productId)
    {
        $inventory = $this->inventoryCollection->create()
            ->addFieldToFilter('product_id', ['eq' => $productId]);

        foreach ($inventory as $item) {
            $item->delete();
        }
    }
}
