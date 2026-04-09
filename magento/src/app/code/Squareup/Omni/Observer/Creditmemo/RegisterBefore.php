<?php
namespace Squareup\Omni\Observer\Creditmemo;

use Magento\Framework\Event\Observer;
use Magento\Sales\Api\OrderRepositoryInterface;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Squareup\Omni\Model\GiftCardRefundFactory;
use Squareup\Omni\Model\ResourceModel\GiftCardRefund as GiftCardRefundResource;

class RegisterBefore implements \Magento\Framework\Event\ObserverInterface
{
    private $orderRepository;

    private $giftCardCollection;

    private $messageManager;

    private $giftCardRefundFactory;

    private $giftCardRefundResource;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        GiftCardCollection $giftCardCollection,
        MessageManager $messageManager,
        GiftCardRefundFactory $giftCardRefundFactory,
        GiftCardRefundResource $giftCardRefundResource
    ){
        $this->orderRepository = $orderRepository;
        $this->giftCardCollection = $giftCardCollection;
        $this->messageManager = $messageManager;
        $this->giftCardRefundFactory = $giftCardRefundFactory;
        $this->giftCardRefundResource = $giftCardRefundResource;
    }

    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getCreditmemo();
        $creditmemo->setAllowZeroGrandTotal(true);
        $input = $observer->getInput();

        $order = $this->orderRepository->get($creditmemo->getOrderId());


        if ($input) {
            $cardData = [];
            $giftCards = $this->giftCardCollection->create()
                ->addFieldToFilter('quote_id', ['eq' => $order->getQuoteId()]);

            foreach ($input as $key => $item) {
                if (strpos($key, 'square_giftcard_') !== false) {
                    $cardData[$key] = $item;
                }
            }


            foreach ($giftCards as $giftCard) {
                $key = 'square_giftcard_' . $giftCard->getEntityId();

                if (isset($cardData[$key])) {
                    if ((double)$cardData[$key] > (double)$giftCard->getAmount()) {
                        $this->messageManager->addErrorMessage(__('Either the refund exceeded the order subtotal or the amount refunded on a gift card exceeded its original value'));
                        throw new \Magento\Framework\Exception\LocalizedException(__('Either the refund exceeded the order subtotal or the amount refunded on a gift card exceeded its original value'));
                    }

                    $creditmemo->setData($key, (double)$cardData[$key]);

                    if ((double)$cardData[$key] > 0) {
                        $giftCardRefund = $this->giftCardRefundFactory->create();
                        $this->giftCardRefundResource->load($giftCardRefund, $giftCard->getEntityId(), 'card_id');
                        $giftCardRefund->setCreditmemoId($creditmemo->getOrderId());
                        $giftCardRefund->setCardId($giftCard->getEntityId());
                        $giftCardRefund->setAmount((double)$cardData[$key]);
                        $this->giftCardRefundResource->save($giftCardRefund);
                    }
                }
            }
        }

        return $this;
    }
}
