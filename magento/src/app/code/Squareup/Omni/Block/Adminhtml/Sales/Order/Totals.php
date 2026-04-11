<?php

namespace Squareup\Omni\Block\Adminhtml\Sales\Order;

use Magento\Framework\View\Element\Template;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;

class Totals extends \Magento\Framework\View\Element\Template
{
    private $giftCardCollection;

    public function __construct(
        Template\Context $context,
        GiftCardCollection $giftCardCollection,
        array $data = []
    ){
        parent::__construct($context, $data);

        $this->giftCardCollection = $giftCardCollection;
    }

    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $quote = $parent->getOrder()->getQuoteId();
        $giftCardAmount = 0;

        $giftCards = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $quote]);

        foreach ($giftCards as $giftCard) {
            $giftCardAmount += $giftCard->getAmount();
        }

        $test = new \Magento\Framework\DataObject(
            [
                'code' => 'squareup_giftcards',
                'strong' => false,
                'value' => -$giftCardAmount,
                'base_value' => -$giftCardAmount,
                'label' => __('Square Gift Cards'),
            ]
        );

        $parent->addTotal($test, 'squareup_giftcards');
        return $this;
    }
}
