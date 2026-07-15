<?php
declare(strict_types=1);

namespace Metatooth\SquareCheckout\Controller\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class Cancel implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly CheckoutSession $checkoutSession,
        private readonly OrderFactory $orderFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
    ) {}

    public function execute()
    {
        $incrementId = $this->request->getParam('order');
        $result = $this->redirectFactory->create();

        if ($incrementId) {
            /** @var Order $order */
            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);

            if ($order->getId()
                && $order->canCancel()
                && $this->checkoutSession->getLastOrderId() == $order->getId()
            ) {
                $order->cancel()
                    ->addCommentToStatusHistory('Cancelled by customer at Square checkout.');
                $this->orderRepository->save($order);
                $this->checkoutSession->restoreQuote();
            }
        }

        $this->messageManager->addNoticeMessage(__('Your payment was cancelled. Your cart has been restored.'));

        return $result->setPath('checkout/cart');
    }
}
