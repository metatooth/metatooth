<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/10/2018
 * Time: 6:32 PM
 */

namespace Squareup\Omni\Model\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Model\OrderFactory as SalesOrderFactory;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Squareup\Omni\Helper\Data as DataHelper;
use Magento\Quote\Model\Quote\ItemFactory as QuoteItemFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\Store\Model\StoreManagerInterface;

class Export extends AbstractModel
{
    /**
     * @var SalesOrderFactory
     */
    private $salesOrderFactory;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var QuoteItemFactory
     */
    private $quoteItemFactory;
    /**
     * @var DataHelper
     */
    private $dataHelper;

    private $apiClient;

    private $lineItems;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\ProductFactory
     */
    private $productFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    private $productData;

    /**
     * Export constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param SalesOrderFactory $salesOrderFactory
     * @param Logger $logger
     * @param ConfigHelper $configHelper
     * @param QuoteItemFactory $quoteItemFactory
     * @param DataHelper $dataHelper
     * @param ProductFactory $productFactory
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        SalesOrderFactory $salesOrderFactory,
        Logger $logger,
        ConfigHelper $configHelper,
        QuoteItemFactory $quoteItemFactory,
        DataHelper $dataHelper,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->salesOrderFactory = $salesOrderFactory;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->dataHelper = $dataHelper;
        $this->apiClient = $this->dataHelper->getClientApi();
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
    }

    public function saveSquareOrderId($squareId, $order)
    {
        if (!empty($order->getIncrementId())) {
            try {
                $order->setSquareOrderId($squareId);
            } catch (\Exception $e) {
                $this->logger->error($e->__toString());
            }
        }
    }

    /**
     * Process order and send it to square
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array|bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processOrder($order, $giftCardAmount)
    {
        if (empty($order->getIncrementId())) {
            return false;
        }

        $this->logger->info(__('Export %s order to Square App', $order->getIncrementId()));
        $request = $this->createRequest($order, $giftCardAmount);

        if (empty($request)) {
            return false;
        }

        $squareOrder = $this->createSquareOrder($request);
        $this->saveSquareOrderId($squareOrder->getId(), $order);

        return $squareOrder;
    }

    /**
     * Prepare the request for order create
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return array
     *
     * @throws \Exception
     */
    public function createRequest($order, $giftCardAmount)
    {
        $storeId = $this->storeManager->getStore();
        $this->lineItems = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() != \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
                continue;
            }

            $this->productData = [];
            $squareVariationId = $this->productFactory->create()
                ->getAttributeRawValue($item->getProductId(), 'square_variation_id', $storeId);
            if (!$squareVariationId) {
                throw new LocalizedException(
                    __(sprintf('Product %s is not synchronized. Order placement failed.', $item->getProductId()))
                );
            }

            $this->prepareProductData($item);

            $tax = 0;

            if ($this->productData['tax'] > 0) {
                if ($giftCardAmount) {
                    $tax = number_format((($this->productData['tax'] / $item->getQtyOrdered()) * 100) /
                        $this->productData['price'], 5);
                } else {
                    $tax = number_format((($this->productData['tax'] / $item->getQtyOrdered()) * 100) /
                        ($this->productData['price'] - (abs($this->productData['discount'] / $item->getQtyOrdered()))), 5);

                }
            }

            if ($item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
                $this->prepareSimpleProduct($order, $item, $squareVariationId, $tax);
            }
        }

        if ($giftCardAmount > 0) {
            foreach ($this->lineItems as &$lineItem) {
                if (isset($lineItem['discounts'])) {
                    foreach ($lineItem['discounts'] as &$discount) {
                        if ($discount['amount_money']['amount'] > 0) {
                            if ($giftCardAmount >= $discount['amount_money']['amount']) {
                                $auxDiscount = $discount['amount_money']['amount'];
                                $discount['amount_money']['amount'] = 0;
                                $giftCardAmount -= $auxDiscount;
                            } else {
                                $discount['amount_money']['amount'] = max(0 , $discount['amount_money']['amount'] - $giftCardAmount);
                                $giftCardAmount = 0;
                            }
                        }
                    }
                }

            }
        }

        if ($order->getShippingAmount()) {
            $shipping = [
                'name' => 'Shipping Amount',
                'quantity' => '1',
                'base_price_money' => [
                    'amount' => (int)$this->dataHelper->processAmount($order->getShippingAmount()),
                    'currency' => $order->getOrderCurrencyCode()
                ],
            ];

            if ($shippingDiscount = (int)ceil($this->dataHelper->processAmount($order->getShippingDiscountAmount()))) {
                $totalDiscount = (int)ceil($this->dataHelper->processAmount($order->getDiscountAmount()));
                $productDiscount = (int)ceil($this->dataHelper->processAmount($this->productData['discount']));
                $calculatedDiscount = $shippingDiscount + $productDiscount;
                $remainingDiscount = 0;

                if ($totalDiscount > $calculatedDiscount) {
                    $remainingDiscount = $totalDiscount - $calculatedDiscount;
                }

                $shippingDiscount += $remainingDiscount;
                $shipping['discounts'] = [
                    [
                        'name' => 'Shipping Discount',
                        'amount_money' => [
                            'amount' => $shippingDiscount,
                            'currency' => $order->getOrderCurrencyCode()
                        ]
                    ]
                ];
            }

            $this->lineItems[] = $shipping;
        }

        return [
            'idempotency_key' => uniqid(),
            'reference_id' => $order->getIncrementId(),
            'line_items' => $this->lineItems
        ];
    }

    /**
     * Prepare product data.
     *
     * @param \Magento\Sales\Model\Order\Item $item
     *
     * @return void
     */
    private function prepareProductData($item)
    {
        $quoteItem = $this->quoteItemFactory->create()->load($item->getQuoteItemId());

        if ($quoteItem->getParentItemId()) {
            $parentItem = $this->quoteItemFactory->create()->load($quoteItem->getParentItemId());
            $this->productData = [
                'price' => $parentItem->getPrice(),
                'tax' => $parentItem->getTaxAmount(),
                'discount' => $parentItem->getDiscountAmount(),
            ];
        } else {
            $this->productData = [
                'price' => $quoteItem->getPrice(),
                'tax' => $quoteItem->getTaxAmount(),
                'discount' => $quoteItem->getDiscountAmount(),
            ];
        }
    }

    /**
     * Prepare simple product.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Item $item
     * @param string $squareVariationId
     * @param float $tax
     *
     * @return void
     */
    public function prepareSimpleProduct($order, $item, $squareVariationId, $tax)
    {
        $this->lineItems[] = [
            'quantity' => (string)(int)$item->getQtyOrdered(),
            'catalog_object_id' => $squareVariationId,
            'taxes' => [
                [
                    'name' => sprintf('Product %s Tax', $item->getName()),
                    'percentage' => (string)$tax
                ]
            ],
            'base_price_money' => [
                'amount' => (int)$this->dataHelper->processAmount($this->productData['price']),
                'currency' => $order->getOrderCurrencyCode()
            ],
            'discounts' => [
                [
                    'name' => 'Product Discount',
                    'amount_money' => [
                        'amount' => (int)abs($this->dataHelper->processAmount($this->productData['discount'])),
                        'currency' => $order->getOrderCurrencyCode()
                    ]
                ]
            ]
        ];
    }

    /**
     * Create Item tax request
     * @param $fullTaxInfo
     * @param $squareVariationId
     * @return array
     */
    public function processItemTaxes($fullTaxInfo, $squareVariationId)
    {
        $taxes = [];
        if (!empty($fullTaxInfo) && is_array($fullTaxInfo)) {
            foreach ($fullTaxInfo as $tax) {
                $taxes[] = [
                    'catalog_object_id' => $squareVariationId,
                    'name' => $tax['rates'][0]['title'],
                    'type' => 'ADDITIVE',
                    'percentage' => (string)$tax['rates'][0]['percent']
                ];
            }
        }

        return $taxes;
    }

    /**
     * Create order
     * @param $request
     * @return bool|\SquareConnect\Model\Order
     * @throws \Exception
     */
    public function createSquareOrder($request)
    {
        try {
            $api = new \SquareConnect\Api\OrdersApi($this->apiClient);
            $request = new \SquareConnect\Model\CreateOrderRequest($request);
            $response = $api->createOrder($this->configHelper->getLocationId(), $request);
            if (empty($response->getErrors())) {
                return $response->getOrder();
            }
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }

        return false;
    }
}
