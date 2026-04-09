<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 8/1/2018
 * Time: 4:18 PM
 */

namespace Squareup\Omni\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;

class SubmitBefore implements ObserverInterface
{
    private $giftCardCollection;

    public function __construct(GiftCardCollection $giftCardCollection)
    {
        $this->giftCardCollection = $giftCardCollection;
    }

    public function execute(Observer $observer)
    {
        $quote = $observer->getQuote();
        $payment = $quote->getPayment();

        $giftCards = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $quote->getId()]);

        if ($giftCards->count() && $payment->getMethod() != 'squareup_payment') {
            throw new \Exception('Payment method is not available');
        }
    }
}
