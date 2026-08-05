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
use Metatooth\SquareCheckout\Helper\Square;

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
            $this->orderRepository->save($order);

            return $result->setUrl($squareUrl);

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Unable to connect to Square. Please try another payment method or contact us.')
            );
            return $result->setPath('checkout/cart');
        }
    }
}
