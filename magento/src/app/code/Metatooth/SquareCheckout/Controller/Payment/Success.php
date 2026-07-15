<?php
declare(strict_types=1);

namespace Metatooth\SquareCheckout\Controller\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Metatooth\SquareCheckout\Helper\Square;

class Success implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly CheckoutSession $checkoutSession,
        private readonly OrderFactory $orderFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Square $square,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
    ) {}

    public function execute()
    {
        $incrementId = $this->request->getParam('order');
        $result = $this->redirectFactory->create();

        if (!$incrementId) {
            return $result->setPath('checkout/cart');
        }

        /** @var Order $order */
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);

        if (!$order->getId() || $this->checkoutSession->getLastOrderId() != $order->getId()) {
            return $result->setPath('checkout/cart');
        }

        if ($order->getState() === Order::STATE_PENDING_PAYMENT) {
            $linkId = $order->getPayment()->getAdditionalInformation('square_payment_link_id');
            $paymentIds = $linkId
                ? $this->square->getPaymentIds($linkId, (int) $order->getStoreId())
                : [];

            if (empty($paymentIds)) {
                // Square redirected here without a completed payment — treat as cancel
                $this->messageManager->addNoticeMessage(__('Your payment was not completed.'));
                return $this->cancelOrder($order, $result);
            }

            $order->setState(Order::STATE_PROCESSING)
                ->setStatus('processing')
                ->addCommentToStatusHistory(
                    __('Payment received via Square (link %1).', $linkId),
                    false,
                    false
                );
            $this->orderRepository->save($order);
        }

        $this->checkoutSession->setLastSuccessQuoteId($this->checkoutSession->getLastQuoteId());

        return $result->setPath('checkout/onepage/success');
    }

    private function cancelOrder(Order $order, \Magento\Framework\Controller\Result\Redirect $result)
    {
        if ($order->canCancel()) {
            $order->cancel()
                ->addCommentToStatusHistory('Cancelled: customer returned from Square without completing payment.');
            $this->orderRepository->save($order);
            $this->checkoutSession->restoreQuote();
        }
        return $result->setPath('checkout/cart');
    }
}
