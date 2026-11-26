<?php
declare(strict_types=1);

namespace Metatooth\SquareCheckout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Square\Legacy\Models\CheckoutOptions;
use Square\Legacy\Models\CreatePaymentLinkRequest;
use Square\Legacy\Models\Money;
use Square\Legacy\Models\QuickPay;
use Square\Legacy\SquareClient;

class Square extends AbstractHelper
{
    public function __construct(
        Context $context,
        private readonly Config $config,
    ) {
        parent::__construct($context);
    }

    /**
     * Create a Square Payment Link for an order.
     *
     * @return array{id: string, url: string}
     */
    public function createPaymentLink(Order $order, string $redirectUrl): array
    {
        $storeId = (int) $order->getStoreId();
        $client = $this->buildClient($storeId);

        $money = new Money();
        $money->setAmount((int) round($order->getGrandTotal() * 100));
        $money->setCurrency($order->getOrderCurrencyCode());

        $quickPay = new QuickPay(
            'Order #' . $order->getIncrementId(),
            $money,
            $this->config->getLocationId($storeId)
        );

        $checkoutOptions = new CheckoutOptions();
        $checkoutOptions->setRedirectUrl($redirectUrl);
        $checkoutOptions->setAllowTipping(false);

        $request = new CreatePaymentLinkRequest();
        $request->setIdempotencyKey(uniqid('mage_', true));
        $request->setQuickPay($quickPay);
        $request->setCheckoutOptions($checkoutOptions);
        $request->setDescription('Metatooth order #' . $order->getIncrementId());

        $response = $client->getCheckoutApi()->createPaymentLink($request);

        if (!$response->isSuccess()) {
            $errors = $response->getErrors();
            throw new \RuntimeException(
                $errors ? $errors[0]->getDetail() : 'Square API error creating payment link'
            );
        }

        $link = $response->getResult()->getPaymentLink();
        return ['id' => $link->getId(), 'url' => $link->getUrl()];
    }

    /**
     * Whether the Square order behind a payment link has been paid.
     *
     * Payment links do not expose payment IDs, so they are read from the
     * tenders on the order Square creates for the link. That order can stay
     * OPEN after payment while its fulfillment is pending, so the payments
     * themselves must be COMPLETED and cover the order total.
     */
    public function isPaymentLinkPaid(string $linkId, int $storeId): bool
    {
        $client = $this->buildClient($storeId);

        $linkResponse = $client->getCheckoutApi()->retrievePaymentLink($linkId);
        if (!$linkResponse->isSuccess()) {
            return false;
        }

        $squareOrderId = $linkResponse->getResult()->getPaymentLink()->getOrderId();
        if (!$squareOrderId) {
            return false;
        }

        $orderResponse = $client->getOrdersApi()->retrieveOrder($squareOrderId);
        if (!$orderResponse->isSuccess()) {
            return false;
        }

        $squareOrder = $orderResponse->getResult()->getOrder();
        $total = (int) $squareOrder?->getTotalMoney()?->getAmount();
        if ($total <= 0) {
            return false;
        }

        $paid = 0;
        foreach ($squareOrder->getTenders() ?? [] as $tender) {
            if (!$tender->getPaymentId()) {
                continue;
            }
            $paymentResponse = $client->getPaymentsApi()->getPayment($tender->getPaymentId());
            if (!$paymentResponse->isSuccess()) {
                continue;
            }
            $payment = $paymentResponse->getResult()->getPayment();
            if ($payment->getStatus() === 'COMPLETED') {
                $paid += (int) $payment->getAmountMoney()?->getAmount();
            }
        }

        return $paid >= $total;
    }

    private function buildClient(int $storeId): SquareClient
    {
        return new SquareClient([
            'accessToken' => $this->config->getAccessToken($storeId),
            'environment' => $this->config->isSandbox($storeId) ? 'sandbox' : 'production',
        ]);
    }
}
