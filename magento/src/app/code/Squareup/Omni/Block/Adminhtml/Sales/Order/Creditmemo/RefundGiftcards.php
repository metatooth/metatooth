<?php

namespace Squareup\Omni\Block\Adminhtml\Sales\Order\Creditmemo;

use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;

class RefundGiftcards extends \Magento\Backend\Block\Template
{
    private $orderRepository;

    private $giftCardCollection;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        GiftCardCollection $giftCardCollection,
        array $data = []
    ){
        parent::__construct($context, $data);

        $this->orderRepository = $orderRepository;
        $this->giftCardCollection = $giftCardCollection;
    }

    public function getRequiredUrl()
    {
        $params = [
            'order_id' => $this->getRequest()->getParam('order_id'),
            'invoice_id' => $this->getRequest()->getParam('invoice_id')
        ];

        return $this->getUrl('square/giftcard/refund', $params);
    }

    public function checkGiftCards()
    {
        $order = $this->orderRepository->get($this->getRequest()->getParam('order_id'));
        $giftCards = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $order->getQuoteId()])
            ->addFieldToFilter('amount', ['gt' => 0]);

        if ($giftCards->count()) {
            return true;
        }

        return false;
    }
}
