<?php
declare(strict_types=1);

namespace Metatooth\SquareCheckout\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Metatooth\SquareCheckout\Helper\Square;
use Psr\Log\LoggerInterface;

class Redirect implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly CheckoutSession $checkoutSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Square $square,
        private readonly UrlInterface $urlBuilder,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute()
    {
        $orderId = (int) $this->request->getParam('order_id');
        $result = $this->redirectFactory->create();

        if (!$orderId || $this->checkoutSession->getLastOrderId() != $orderId) {
            return $result->setPath('checkout/cart');
        }

        try {
            $order = $this->orderRepository->get($orderId);
            $returnUrl = $this->urlBuilder->getUrl('squarecheckout/payment/success', [
                '_query' => ['order' => $order->getIncrementId()],
            ]);

            ['id' => $linkId, 'url' => $squareUrl] = $this->square->createPaymentLink($order, $returnUrl);

            $order->getPayment()->setAdditionalInformation('square_payment_link_id', $linkId);
            // payment_action=order leaves the order in new/processing; Success verifies only pending_payment
            $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
            $this->orderRepository->save($order);

            return $result->setUrl($squareUrl);

        } catch (\Throwable $e) {
            $this->logger->error('Metatooth_SquareCheckout: ' . $e->getMessage(), ['exception' => $e]);
            $this->messageManager->addErrorMessage(
                __('Unable to connect to Square. Please try another payment method or contact us.')
            );
            return $result->setPath('checkout/cart');
        }
    }
}
