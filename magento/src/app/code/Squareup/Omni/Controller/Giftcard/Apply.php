<?php

namespace Squareup\Omni\Controller\GiftCard;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\SessionFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;
use Squareup\Omni\Model\ResourceModel\GiftCard as GiftCardResource;
use Squareup\Omni\Model\GiftCardFactory;

class Apply extends \Magento\Framework\App\Action\Action
{
    private $sessionFactory;

    private $quoteRepository;

    private $jsonResponseFactory;

    private $giftCardCollection;

    private $giftCardResource;

    private $giftCardFactory;

    public function __construct(
        Context $context,
        SessionFactory $sessionFactory,
        CartRepositoryInterface $quoteRepository,
        JsonFactory $jsonResponseFactory,
        GiftCardCollection $giftCardCollection,
        GiftCardResource $giftCardResource,
        GiftCardFactory $giftCardFactory
    ){
        parent::__construct($context);

        $this->sessionFactory = $sessionFactory;
        $this->quoteRepository = $quoteRepository;
        $this->jsonResponseFactory = $jsonResponseFactory;
        $this->giftCardCollection = $giftCardCollection;
        $this->giftCardResource = $giftCardResource;
        $this->giftCardFactory = $giftCardFactory;
    }

    public function execute()
    {
        $quote = $this->sessionFactory->create()->getQuote();
        $cardCode = $this->_request->getParam('card_code');
        $cardNonce = $this->_request->getParam('card_nonce');

        $collection = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $quote->getId()])
            ->addFieldToFilter('card_code', ['eq' => $cardCode]);

        if ($collection->count()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $this->quoteRepository->save($quote->collectTotals());
            $giftCard = $collection->getFirstItem();
            $data = [
                'card_code' => $giftCard->getCardCode(),
                'duplicate' => true,
                'total' => $quote->getGrandTotal()
            ];

            return $this->jsonResponseFactory->create()
                ->setData($data);
        }

        $giftCard = $this->giftCardFactory->create();
        $balance = $giftCard->getBalance($cardNonce);
        $giftCard->setQuoteId($quote->getId());
        $giftCard->setCardCode($cardCode);
        $giftCard->setCardNonce($cardNonce);
        $giftCard->setCurrentAmount($balance);
        $this->giftCardResource->save($giftCard);

        $quote->getShippingAddress()->setCollectShippingRates(true);
        $this->quoteRepository->save($quote->collectTotals());

        $collection = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $quote->getId()])
            ->addFieldToFilter('card_code', ['eq' => $cardCode]);

        $giftCard = $collection->getFirstItem();
        $data = [
            'card_code' => $giftCard->getCardCode(),
            'amount' => $giftCard->getAmount(),
            'duplicate' => false,
            'total' => $quote->getGrandTotal()
        ];

        return $this->jsonResponseFactory->create()->setData($data);
    }
}
