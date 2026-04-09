<?php

namespace Squareup\Omni\Controller\GiftCard;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\SessionFactory;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;
use Squareup\Omni\Model\ResourceModel\GiftCard as GiftCardResource;
use Magento\Quote\Api\CartRepositoryInterface;

class Remove extends \Magento\Framework\App\Action\Action
{
    private $jsonResponseFactory;

    private $sessionFactory;

    private $giftCardCollection;

    private $giftCardResource;

    private $quoteRepository;

    public function __construct(
        Context $context,
        JsonFactory $jsonResponseFactory,
        SessionFactory $sessionFactory,
        GiftCardCollection $giftCardCollection,
        GiftCardResource $giftCardResource,
        CartRepositoryInterface $quoteRepository
    ){
        parent::__construct($context);

        $this->jsonResponseFactory = $jsonResponseFactory;
        $this->sessionFactory = $sessionFactory;
        $this->giftCardCollection = $giftCardCollection;
        $this->giftCardResource = $giftCardResource;
        $this->quoteRepository = $quoteRepository;
    }

    public function execute()
    {
        $quote = $this->sessionFactory->create()->getQuote();
        $cardCode = $this->_request->getParam('card_code');

        $giftCards = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $quote->getId()])
            ->addFieldToFilter('card_code', ['eq' => $cardCode]);

        /** @var \Squareup\Omni\Model\GiftCard $giftCard */
        foreach ($giftCards as $giftCard) {
            $this->giftCardResource->delete($giftCard);
        }

        $quote->getShippingAddress()->setCollectShippingRates(true);
        $this->quoteRepository->save($quote->collectTotals());
        $giftCards = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $quote->getId()]);

        $response = [];

        foreach ($giftCards as $giftCard) {
            $response[] = [
                'card_code' => $giftCard->getCardCode(),
                'amount' => $giftCard->getAmount()
             ];
        }

        return $this->jsonResponseFactory->create()->setData($response);
    }

}
