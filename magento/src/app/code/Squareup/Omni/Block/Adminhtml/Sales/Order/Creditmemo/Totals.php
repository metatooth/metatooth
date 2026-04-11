<?php

namespace Squareup\Omni\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;
use Magento\Framework\App\ResourceConnection;

class Totals extends \Magento\Framework\View\Element\Template
{
    private $giftCardCollection;

    private $orderRepository;

    private $resourceConnection;

    public function __construct(
        Template\Context $context,
        OrderRepositoryInterface $orderRepository,
        GiftCardCollection $giftCardCollection,
        ResourceConnection $resourceConnection,
        array $data = []
    ){
        parent::__construct($context, $data);

        $this->orderRepository = $orderRepository;
        $this->giftCardCollection = $giftCardCollection;
        $this->resourceConnection = $resourceConnection;
    }

    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $total = new \Magento\Framework\DataObject(['code' => 'giftcard_agjustments', 'block_name' => $this->getNameInLayout()]);
        $parent->addTotal($total);

        return $this;
    }

    public function getGiftCards()
    {
        $parent = $this->getParentBlock();
        $creditmemo = $parent->getSource();
        $order = $this->orderRepository->get($creditmemo->getOrderId());
        $giftCards = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $order->getQuoteId()])
            ->addFieldToFilter('amount', ['gt' => 0]);

        return $giftCards;
    }

    public function getSource()
    {
        $parent = $this->getParentBlock();
        $creditmemo = $parent->getSource();

        return $creditmemo;
    }
}
