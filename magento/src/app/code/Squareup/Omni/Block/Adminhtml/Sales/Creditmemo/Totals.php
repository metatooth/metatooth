<?php

namespace Squareup\Omni\Block\Adminhtml\Sales\Creditmemo;

use Magento\Framework\View\Element\Template;
use Squareup\Omni\Model\ResourceModel\GiftCardRefund\CollectionFactory as GiftCardRefundCollection;
use Magento\Framework\App\ResourceConnection;

class Totals extends \Magento\Framework\View\Element\Template
{
    private $giftCardRefundCollection;

    private $resourceConnection;

    public function __construct(
        Template\Context $context,
        GiftCardRefundCollection $giftCardRefundCollection,
        ResourceConnection $resourceConnection,
        array $data = []
    ){
        parent::__construct($context, $data);

        $this->giftCardRefundCollection = $giftCardRefundCollection;
        $this->resourceConnection = $resourceConnection;
    }

    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $total = new \Magento\Framework\DataObject(['code' => 'giftcard_refunds', 'block_name' => $this->getNameInLayout()]);
        $parent->addTotal($total);

        return $this;
    }

    public function getGiftCards()
    {
        $parent = $this->getParentBlock();
        $creditmemo = $parent->getSource();
        $reundIds = explode(',', $creditmemo->getGiftcardRefunds());
        $giftCards = $this->giftCardRefundCollection->create()
            ->addFieldToFilter('main_table.entity_id', ['in' => $reundIds])
            ->join(
                [
                    'gift_card' => $this->resourceConnection->getTableName('squareup_omni_giftcard')
                ],
                'main_table.card_id = gift_card.entity_id',
                ['refunded_amount' => 'main_table.amount', 'card_code' => 'gift_card.card_code']
            );

        return $giftCards;
    }

    public function getSource()
    {
        $parent = $this->getParentBlock();
        $creditmemo = $parent->getSource();

        return $creditmemo;
    }
}
