<?php
declare(strict_types=1);

namespace Metatooth\SquareCheckout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Square\SquareClient;
use Square\Models\CheckoutOptions;
use Square\Models\CreatePaymentLinkRequest;
use Square\Models\Money;
use Square\Models\QuickPay;

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
     * Return the payment IDs attached to a previously-created payment link.
     *
     * @return string[]
     */
    public function getPaymentIds(string $linkId, int $storeId): array
    {
        $response = $this->buildClient($storeId)->getCheckoutApi()->retrievePaymentLink($linkId);
        if (!$response->isSuccess()) {
            return [];
        }
        return $response->getResult()->getPaymentLink()->getPaymentIds() ?? [];
    }

    private function buildClient(int $storeId): SquareClient
    {
        return new SquareClient([
            'accessToken' => $this->config->getAccessToken($storeId),
            'environment' => $this->config->isSandbox($storeId) ? 'sandbox' : 'production',
        ]);
    }
}
