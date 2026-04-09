<?php

namespace Squareup\Omni\Controller\GiftCard;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Squareup\Omni\Model\GiftCard;

class CheckBalance extends \Magento\Framework\App\Action\Action
{
    private $jsonResponseFactory;

    private $giftCard;

    public function __construct(
        Context $context,
        JsonFactory $jsonResponseFactory,
        GiftCard $giftCard
    ){
        parent::__construct($context);

        $this->jsonResponseFactory = $jsonResponseFactory;
        $this->giftCard = $giftCard;
    }

    public function execute()
    {
        $cardNonce = $this->_request->getParam('card_nonce');
        $balance = $this->giftCard->getBalance($cardNonce);

        return $this->jsonResponseFactory->create()->setData(['balance' => $balance]);
    }

}
