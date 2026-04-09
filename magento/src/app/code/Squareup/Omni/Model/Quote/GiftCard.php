<?php

namespace Squareup\Omni\Model\Quote;

use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;
use Squareup\Omni\Model\ResourceModel\GiftCard as GiftCardResource;

class GiftCard extends AbstractTotal
{
    private $giftCardCollection;

    private $giftCardResource;

    public function __construct(GiftCardCollection $giftCardCollection, GiftCardResource $giftCardResource)
    {
        $this->giftCardCollection = $giftCardCollection;
        $this->giftCardResource = $giftCardResource;
    }

    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $discount = 0;
        $subtotal = $quote->getSubtotal();
        $giftCards = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $quote->getId()])
            ->setOrder('current_amount');

        foreach ($giftCards as $giftCard) {
            if ($subtotal >= 0) {
                if ($subtotal >= $giftCard->getCurrentAmount()) {
                    $discount += $giftCard->getCurrentAmount();
                    $subtotal -= $giftCard->getCurrentAmount();
                    $giftCard->setAmount($giftCard->getCurrentAmount());
                } else {
                    $giftCard->setAmount($subtotal);
                    $discount += $subtotal;
                    $subtotal -= $subtotal;
                }

                $this->giftCardResource->save($giftCard);
            }
        }
        $baseAmount = $total->getBaseSubtotal() + $total->getBaseDiscountAmount();

        if ($baseAmount > 0) {
            $total->setTotalAmount($this->getCode(), -$discount);
            $total->setBaseTotalAmount($this->getCode(), -$discount);

            foreach ($quote->getAllItems() as $item) {
                if ($discount > 0) {
                    $discountItem = $item->getPrice() * $item->getQty() - $item->getDiscountAmount();

                    if ($discount >= $discountItem) {
                        $item->setBaseDiscountAmount($item->getBaseDiscountAmount() + $discountItem);
                        $item->setDiscountAmount($item->getDiscountAmount() + $discountItem);
                        $discount -= $discountItem;
                    } else {
                        $item->setBaseDiscountAmount($item->getBaseDiscountAmount() + $discount);
                        $item->setDiscountAmount($item->getDiscountAmount() + $discount);
                        $discount -= $discount;
                    }
                }
            }
        }

        return $this;
    }
}
