<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/15/2018
 * Time: 11:46 AM
 */

namespace Squareup\Omni\Observer\AdminGrid;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;
use Magento\Sales\Model\ResourceModel\Order\Invoice as InvoiceResource;

class SynchronizeItems implements ObserverInterface
{
    /**
     * @var \Squareup\Omni\Helper\Config
     */
    private $configHelper;
    /**
     * @var \Squareup\Omni\Model\Inventory\Export
     */
    private $export;

    private $giftCardCollection;

    private $invoiceResource;

    private $orderRepository;

    /**
     * SynchronizeItems constructor.
     * @param \Squareup\Omni\Helper\Config $configHelper
     * @param \Squareup\Omni\Model\Inventory\Export $export
     */
    public function __construct(
        \Squareup\Omni\Helper\Config $configHelper,
        \Squareup\Omni\Model\Inventory\Export $export,
        GiftCardCollection $giftCardCollection,
        InvoiceResource $invoiceResource,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->configHelper = $configHelper;
        $this->export = $export;
        $this->giftCardCollection = $giftCardCollection;
        $this->invoiceResource = $invoiceResource;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Synchronize items inventory on order creation
     *
     * @param $observer
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $payment_method_code = $invoice->getOrder()->getPayment()->getMethodInstance()->getCode();
        $ids = [];

        $order = $this->orderRepository->get($invoice->getOrderId());
        $giftCards = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $order->getQuoteId()])
            ->addFieldToFilter('amount', ['gt' => 0]);

        if ($giftCards->count()) {
            $invoice->setIsUsedForRefund(false);
            $this->invoiceResource->saveAttribute($invoice, 'is_used_for_refund');
        }

        //$this->invoiceResource->saveAttribute($invoice, 'is_used_for_refund');
        if ($payment_method_code == 'squareup_payment') {
            return $this;
        }

        if (false === $this->configHelper->isInventoryEnabled() || false === $this->configHelper->isCatalogEnabled()) {
            return $this;
        }

        foreach ($invoice->getAllItems() as $item) {
            $ids[] = $item->getProductId();
        }

        $this->export->start($ids);

        return $this;
    }
}
