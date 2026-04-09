<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/16/2018
 * Time: 11:07 AM
 */

namespace Squareup\Omni\Observer\Product;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Squareup\Omni\Model\Catalog\Product;
use Magento\Catalog\Model\ProductFactory;

class ResetChildren implements ObserverInterface
{
    /**
     * @var Product
     */
    private $product;
    /**
     * @var Registry
     */
    private $registry;

    private $productFactory;

    /**
     * ResetChildren constructor.
     * @param Product $product
     * @param Registry $registry
     */
    public function __construct(
        Product $product,
        Registry $registry,
        ProductFactory $productFactory
    ) {
        $this->product = $product;
        $this->registry = $registry;
        $this->productFactory = $productFactory;
    }

    /**
     * @param Observer $observer
     * @return ResetChildren
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $product = $this->productFactory->create()->load($product->getId());

        if (!empty($this->registry->registry('delete_product_squareid'))) {
            $this->registry->unregister('delete_product_squareid');
        }
        $this->registry->register('delete_product_squareid', $product->getSquareId());

        if ($product->getTypeId() != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return $this;
        }

        $ids = $this->product->resetChildrenIds($product);
        $this->registry->register('delete_product_' . $product->getId(), json_encode($ids));

        return $this;
    }
}
