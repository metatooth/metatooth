<?php
/**
 * SquareUp
 *
 * UpdateInventory Controller
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Controller\Adminhtml\Inventory;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Model\Location\Import;
use Magento\Framework\Controller\ResultFactory;
use Squareup\Omni\Model\ResourceModel\Location\CollectionFactory as LocationCollection;
use Magento\Catalog\Model\ProductFactory;
use Squareup\Omni\Model\ResourceModel\Inventory\CollectionFactory as InventoryCollection;
use Squareup\Omni\Model\Inventory\Export;
use Magento\CatalogInventory\Api\StockRegistryInterface;

/**
 * Class UpdateInventory
 */
class Update extends Action
{
    /**
     * @var Config
     */
    private $configHelper;

    private $locationCollection;

    private $productFactory;

    private $inventoryCollection;

    private $inventoryExport;

    private $stockRegistry;

    public function __construct(
        Config $config,
        LocationCollection $locationCollection,
        ProductFactory $productFactory,
        InventoryCollection $inventoryCollection,
        Export $export,
        StockRegistryInterface $stockRegistry,
        Action\Context $context
    ) {
        $this->configHelper = $config;
        $this->locationCollection = $locationCollection;
        $this->productFactory = $productFactory;
        $this->inventoryCollection = $inventoryCollection;
        $this->inventoryExport = $export;
        $this->stockRegistry = $stockRegistry;
        parent::__construct($context);
    }

    public function execute()
    {

        if (!$this->configHelper->getSor()) {
            return null;
        }

        if (false === $this->configHelper->isInventoryEnabled()) {
            return null;
        }


        $params = $this->_request->getParams();

        try {
            $product = $this->productFactory->create()
                ->load($params['productId']);

            if (!$product->getWebsiteIds()) {
                $this->_response->setHttpResponseCode(400);
                return;
            }

            $location = $this->locationCollection->create()
                ->addFieldToFilter('square_id', ['eq' => $params['location_id']])
                ->addFieldToSelect('square_id');

            $locationId = $location->getFirstItem()->getSquareId();

            $product = $this->productFactory->create()->load($params['productId']);
            $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
            $stockItem->setQty($params['quantity']);
            $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);

            $inventory = $this->inventoryCollection->create()
                ->addFieldToFilter('location_id', ['eq' => $locationId])
                ->addFieldToFilter('product_id', ['eq' => $params['productId']]);

            $inventory = $inventory->getFirstItem();
            $inventory->setQuantity($params['quantity']);
            $inventory->save();

            $this->inventoryExport->start([$params['productId']], $locationId, $params['quantity']);
            $this->messageManager->addSuccessMessage("Product updated successfully");
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage("Product update failed");
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}
