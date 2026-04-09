<?php

namespace Squareup\Omni\Controller\GiftCard;

use Magento\Framework\App\Action\Context;
use Squareup\Omni\Model\ResourceModel\GiftCard as GiftCardResource;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;
use Magento\Checkout\Model\SessionFactory;
use Magento\Quote\Api\CartRepositoryInterface;

class Reset extends \Magento\Framework\App\Action\Action
{
    private $giftCardResource;

    private $giftCardCollection;

    private $sessionFactory;

    private $cartRepository;

    public function __construct(
        Context $context,
        GiftCardResource $giftCardResource,
        GiftCardCollection $giftCardCollection,
        SessionFactory $sessionFactory,
        CartRepositoryInterface $cartRepository
    ){
        parent::__construct($context);

        $this->giftCardResource = $giftCardResource;
        $this->giftCardCollection = $giftCardCollection;
        $this->sessionFactory = $sessionFactory;
        $this->cartRepository = $cartRepository;
    }

    public function execute()
    {
        $quote = $this->sessionFactory->create()->getQuote();
        $giftCards = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $quote->getId()]);

        foreach ($giftCards as $giftCard) {
            $this->giftCardResource->delete($giftCard);
        }

        $quote->getShippingAddress()->setCollectShippingRates(true);
        $this->cartRepository->save($quote->collectTotals());
    }
}
