<?php
declare(strict_types=1);

namespace Metatooth\SquareCheckout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Square\Legacy\Models\CheckoutOptions;
use Square\Legacy\Models\CreatePaymentLinkRequest;
use Square\Legacy\Models\Money;
use Square\Legacy\Models\OrderState;
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

        $quickPay = new QuickPay();
        $quickPay->setName('Order #' . $order->getIncrementId());
        $quickPay->setPriceMoney($money);
        $quickPay->setLocationId($this->config->getLocationId($storeId));

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
     * Payment links do not expose payment IDs; the order Square creates for
     * the link is COMPLETED once a tender has been captured against it.
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
        return $squareOrder !== null && $squareOrder->getState() === OrderState::COMPLETED;
    }

    private function buildClient(int $storeId): SquareClient
    {
        return new SquareClient([
            'accessToken' => $this->config->getAccessToken($storeId),
            'environment' => $this->config->isSandbox($storeId) ? 'sandbox' : 'production',
        ]);
    }
}
