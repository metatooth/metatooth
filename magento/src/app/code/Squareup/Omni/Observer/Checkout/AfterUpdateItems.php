<?php

namespace Squareup\Omni\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\SessionFactory;
use Magento\Quote\Api\CartRepositoryInterface;

class AfterUpdateItems implements ObserverInterface
{
    private $sessionFactory;

    private $cartRepository;

    public function __construct(SessionFactory $sessionFactory, CartRepositoryInterface $cartRepository)
    {
        $this->sessionFactory = $sessionFactory;
        $this->cartRepository = $cartRepository;
    }

    public function execute(Observer $observer)
    {
        $quote = $this->sessionFactory->create()->getQuote();
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $this->cartRepository->save($quote->collectTotals());
    }
}
