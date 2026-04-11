<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/15/2018
 * Time: 10:43 AM
 */

namespace Squareup\Omni\Observer\Product;

use Magento\Framework\Event\Observer;
use Squareup\Omni\Model\ResourceModel\Product as SquareProductResource;
use Squareup\Omni\Model\ResourceModel\Location\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Registry;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Squareup\Omni\Logger\Debugger;

class SaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Squareup\Omni\Helper\Config
     */
    private $configHelper;
    /**
     * @var \Squareup\Omni\Model\Catalog\Product
     */
    private $product;

    /**
     * @var SquareProductResource
     */
    private $squareProductResource;

    /**
     * @var CollectionFactory
     */
    private $locationCollection;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Debugger
     */
    private $debugger;
    /**
     * SaveAfter constructor
     *
     * @param \Squareup\Omni\Helper\Config $configHelper
     * @param \Squareup\Omni\Model\Catalog\Product $product
     * @param SquareProductResource $squareProductResource
     * @param ProductResource $productResource
     * @param CollectionFactory $locationCollection
     * @param Registry $registry
     * @param Debugger $debugger
     */
    public function __construct(
        \Squareup\Omni\Helper\Config $configHelper,
        \Squareup\Omni\Model\Catalog\Product $product,
        SquareProductResource $squareProductResource,
        ProductResource $productResource,
        CollectionFactory $locationCollection,
        Registry $registry,
        Debugger $debugger
    ) {
        $this->configHelper = $configHelper;
        $this->product = $product;
        $this->squareProductResource = $squareProductResource;
        $this->locationCollection = $locationCollection;
        $this->productResource = $productResource;
        $this->registry = $registry;
        $this->debugger = $debugger;
    }

    /**
     * @param Observer $observer
     * @return SaveAfter
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()
            ->getProduct();

        $this->registry->registry('square_product', $product->getId());

        if ($this->configHelper->getSor() == \Squareup\Omni\Model\System\Config\Source\Options\Records::SQUARE) {
            return $this;
        }

        if (false === $this->configHelper->isCatalogEnabled()) {
            return $this;
        }

        $notInSquare = empty($product->getSquareId()) ? true : false;
        if ($notInSquare === true) {
            $this->product->createProduct($product);
        } else {
            $this->product->updateProduct($product);
        }

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $product->setSquareVariationId(null);
            $this->productResource->saveAttribute($product, 'square_variation_id');
        }

        return $this;
    }
}
