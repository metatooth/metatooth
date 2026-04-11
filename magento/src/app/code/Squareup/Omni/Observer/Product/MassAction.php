<?php
/**
 * SquareUp
 *
 * StockAction Observer
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Observer\Product;

use Magento\Catalog\Helper\Product\Edit\Action\Attribute;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Squareup\Omni\Logger\Logger;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

class MassAction implements ObserverInterface
{
    public $attributeHelper;
    public $productModel;
    public $saveAfter;
    public $logger;
    public $productResource;

    public function __construct(
        Attribute $attributeHelper,
        Product $productModel,
        SaveAfter $saveAfter,
        Logger $logger,
        ProductResource $productResource
    )
    {
        $this->attributeHelper = $attributeHelper;
        $this->productModel = $productModel;
        $this->saveAfter = $saveAfter;
        $this->logger = $logger;
        $this->productResource = $productResource;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $products = $this->attributeHelper->getProducts();

        foreach ($products as $product) {
            $event = new DataObject();
            $data = $this->productResource
                ->getAttributeRawValue($product->getId(), ['square_id', 'square_variation_id', 'name', 'description', 'price'], 0);
            $product->addData($data);
            $event->setData('product', $product);
            $observer = new Observer();
            $observer->setEvent($event);
            try {
                $this->saveAfter->execute($observer);
            } catch (\Exception $e) {
                $this->logger->error($e->__toString());
            }

        }
    }
}
