<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/8/2018
 * Time: 5:22 PM
 */

namespace Squareup\Omni\Model;

use Magento\Sales\Model\Order\Creditmemo;
use Squareup\Omni\Model\CardFactory;
use Squareup\Omni\Logger\Logger as SquareupLogger;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Squareup\Omni\Helper\Data as DataHelper;
use Squareup\Omni\Model\Order\Export as ExportOrder;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Exception\LocalizedException;
use Squareup\Omni\Model\System\Config\Source\Options\Mode;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;
use Squareup\Omni\Model\ResourceModel\GiftCard as GiftCardResource;
use Squareup\Omni\Model\GiftCardRefundFactory;
use Squareup\Omni\Model\ResourceModel\GiftCardRefund as GiftCardRefundResource;

class Payment extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'squareup_payment';

    protected $_code = self::CODE;

    protected $_canAuthorize = true;

    protected $_canCapture = true;

    protected $_canVoid = true;

    protected $_canRefund = true;

    /**
     * @var SquareupLogger
     */
    private $squareupLogger;
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var int
     */
    private $locationId;
    /**
     * @var string
     */
    private $authToken;
    /**
     * @var string
     */
    private $apiClient;
    /**
     * @var DataHelper
     */
    private $dataHelper;
    /**
     * @var ExportOrder
     */
    private $exportOrder;
    /**
     * @var Card
     */
    private $card;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;
    /**
     * @var MessageManager
     */
    private $messageManager;

    private $giftCardCollection;

    private $giftCardResource;

    private $giftCardRefundFactory;

    private $giftCardRefundResource;

    /**
     * Payment constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param SquareupLogger $squareupLogger
     * @param ConfigHelper $configHelper
     * @param DataHelper $dataHelper
     * @param ExportOrder $exportOrder
     * @param Card $card
     * @param CustomerFactory $customerFactory
     * @param MessageManager $messageManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        SquareupLogger $squareupLogger,
        ConfigHelper $configHelper,
        DataHelper $dataHelper,
        ExportOrder $exportOrder,
        Card $card,
        CustomerFactory $customerFactory,
        MessageManager $messageManager,
        GiftCardCollection $giftCardCollection,
        GiftCardResource $giftCardResource,
        GiftCardRefundFactory $giftCardRefundFactory,
        GiftCardRefundResource $giftCardRefundResource,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );

        $this->squareupLogger = $squareupLogger;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->exportOrder = $exportOrder;
        $this->card = $card;
        $this->customerFactory = $customerFactory;
        $this->messageManager = $messageManager;
        $this->giftCardCollection = $giftCardCollection;
        $this->giftCardResource = $giftCardResource;
        $this->giftCardRefundFactory = $giftCardRefundFactory;
        $this->giftCardRefundResource = $giftCardRefundResource;
    }

    /**
     * Assign the custom data to the payment
     * @param mixed $data
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        $info = $this->getInfoInstance();
        $additionalData = $data->getAdditionalData();
        $info->setAdditionalInformation('display_form', $additionalData['display_form']);

        if ($additionalData['display_form']) {
            if ($additionalData['nonce']) {
                $info->setAdditionalInformation('squareup_nonce', $additionalData['nonce']);
            }

            if ($additionalData['buyerVerificationToken']) {
                $info->setAdditionalInformation('buyerVerificationToken', $additionalData['buyerVerificationToken']);
            }

            $ccData = [
                'cc_type' => null,
                'cc_last_4' => null,
                'cc_number' => null,
                'cc_exp_month' => null,
                'cc_exp_year' => null
            ];
            if (array_key_exists('cc_type', $additionalData)) {
                $info->setAdditionalInformation('cc_type', $additionalData['cc_type']);
                $ccData['cc_type'] =  $additionalData['cc_type'];
            }

            if (array_key_exists('cc_last_4', $additionalData)) {
                $info->setAdditionalInformation('cc_last_4', $additionalData['cc_last_4']);
                $ccData['cc_last_4'] =  $additionalData['cc_last_4'];
            }

            if (array_key_exists('cc_number', $additionalData)) {
                $info->setAdditionalInformation('cc_number', $additionalData['cc_number']);
                $ccData['cc_number'] =  $additionalData['cc_number'];
            }

            if (array_key_exists('cc_exp_month', $additionalData)) {
                $info->setAdditionalInformation('cc_exp_month', $additionalData['cc_exp_month']);
                $ccData['cc_exp_month'] =  $additionalData['cc_exp_month'];
            }

            if (array_key_exists('cc_exp_year', $additionalData)) {
                $info->setAdditionalInformation('cc_exp_year', $additionalData['cc_exp_year']);
                $ccData['cc_exp_year'] =  $additionalData['cc_exp_year'];
            }

            $info->addData($ccData);
        }

        /* if customer selected to save payment card set save_square_card flag */
        if (!empty($additionalData['save_square_card']) && (int)$additionalData['save_square_card'] == 1) {
            $info->setAdditionalInformation('save_square_card', 1);
        } else {
            $info->setAdditionalInformation('save_square_card', null);
        }

        return $this;
    }

    /**
     * Check if the custom data is valid
     * @return $this
     * @throws \Exception
     */
    public function validate()
    {
        $info = $this->getInfoInstance();

        $errorMsg = false;

        $quoteAdditionalData = $info->getAdditionalInformation();
        if (!$quoteAdditionalData['display_form']) {
            return $this;
        }

        if (!$quoteAdditionalData['squareup_nonce']) {
            $errorMsg = __("Nonce is a required field.\n");
        }

        if ($errorMsg) {
            $this->squareupLogger->error($errorMsg);
            $this->_logger->error($errorMsg);
            throw new LocalizedException($errorMsg);
        }

        return $this;
    }

    /**
     * Capture Payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $store = $payment->getOrder()->getStore();
        $storeId = $store->getStoreId();
        $websiteId = $store->getWebsiteId();

        if ($locationId = $this->configHelper->getLocationId('store', $storeId)) {}
        elseif ($locationId = $this->configHelper->getLocationId('website', $websiteId)) {}
        else {
            $locationId = $this->configHelper->getLocationId();
        }

        try {
            if (!$this->canCapture()) {
                throw new LocalizedException(__('Capture action is not available.'));
            }

            $this->initApi();
            if (self::ACTION_AUTHORIZE_CAPTURE === $this->configHelper->getPaymentAction()) {
                $this->charge($payment, $amount, self::ACTION_AUTHORIZE_CAPTURE);
                return $this;
            }

            if (self::ACTION_AUTHORIZE === $this->configHelper->getPaymentAction()
                && \Squareup\Omni\Model\Card::ALLOW_ONLY_CARD_ON_FILE == $this->dataHelper->getCardOnFileOption()) {
                $this->charge($payment, $amount, self::ACTION_AUTHORIZE_CAPTURE);
                return $this;
            }

            $transactionsId = $payment->getTransactionId();
            $transactionsId = str_replace('-capture', '', $transactionsId);

            $transactionsApi = new \SquareConnect\Api\TransactionsApi($this->apiClient);
            $transactionsApi->captureTransaction($locationId, $transactionsId);
        } catch (\SquareConnect\ApiException $e) {
            $this->squareupLogger->error($e->__toString());
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__($e->getResponseBody()->errors[0]->detail));
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * Authorize the amount with Square
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this|\Magento\Payment\Model\InfoInterface
     * @throws \Exception
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            if (!$this->canAuthorize()) {
                throw new LocalizedException(__('Authorize action is not available.'));
            }

            $this->initApi();
            if (Card::ALLOW_ONLY_CARD_ON_FILE == $this->dataHelper->getCardOnFileOption()) {
                $this->cardSaveOnFile($payment);
                return $this;
            }

            $this->charge($payment, $amount);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return Payment|void
     * @throws LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /**
         * @var $payment Creditmemo
         */
        try {
            if (!$this->canRefund()) {
                throw new LocalizedException(__('Refund action is not available.'));
            }

            $order = $payment->getOrder();
            $this->initApi();
            $transactionsApi = new \SquareConnect\Api\TransactionsApi($this->apiClient);

            $store = $order->getStore();
            $storeId = $store->getStoreId();
            $websiteId = $store->getWebsiteId();

            if ($locationId = $this->configHelper->getLocationId('store', $storeId)) {}
            elseif ($locationId = $this->configHelper->getLocationId('website', $websiteId)) {}
            else {
                $locationId = $this->configHelper->getLocationId();
            }

            $transactionResponse = $transactionsApi->retrieveTransaction(
                $locationId,
                $payment->getSquareupTransaction()
            );
            $transaction = $transactionResponse->getTransaction();
        } catch (\SquareConnect\ApiException $e) {
            $this->squareupLogger->error($e->__toString());
            $this->_logger->error($e->getMessage());
            $exceptionMessage = $this->parseSquareException($e);
            throw new LocalizedException(__($exceptionMessage));
        } catch (\Exception $e) {
            $this->squareupLogger->error($e->__toString());
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }

        $sendAmount = $this->dataHelper->processAmount($amount);
        $idempotencyKey = uniqid();
        $tenderId = null;

        foreach ($transaction->getTenders() as $tender) {
            if ('SQUARE_GIFT_CARD' != $tender->getCardDetails()->getCard()->getCardBrand()) {
                $tenderId = $tender->getId();
            }
        }

        if ($sendAmount > 0) {
            $data = [
                'tender_id' => $tenderId,
                'amount_money' => [
                    'amount' => $sendAmount,
                    'currency' => $order->getOrderCurrencyCode()
                ],
                'idempotency_key' => $idempotencyKey,
                'reason' => 'Refund order #' . $order->getIncrementId() . ' from location #' . $locationId
            ];
            $requestData = new \SquareConnect\Model\CreateRefundRequest($data);
            $this->squareupLogger->info('refund transaction id# ' . $payment->getSquareupTransaction());
            try {
                $transactionsApi->createRefund($locationId, $payment->getSquareupTransaction(), $requestData);
            } catch (\SquareConnect\ApiException $e) {
                $this->squareupLogger->error($e->__toString());
                $this->_logger->error($e->getMessage());
                $exceptionMessage = $this->parseSquareException($e);
                throw new LocalizedException(__($exceptionMessage));
            } catch (\Exception $e) {
                $this->squareupLogger->error($e->__toString());
                $this->_logger->error($e->getMessage());
                throw new LocalizedException(__($e->getMessage()));
            }
        }

        return $this;
    }

    /**
     * Voiding the payment to Square
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this|\Magento\Payment\Model\InfoInterface
     * @throws \Exception
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $store = $payment->getOrder()->getStore();
        $storeId = $store->getId();
        $websiteId = $store->getWebsiteId();

        if ($locationId = $this->configHelper->getLocationId('store', $storeId)) {}
        elseif ($locationId = $this->configHelper->getLocationId('website', $websiteId)) {}
        else {
            $locationId = $this->configHelper->getLocationId();
        }
        try {
            if (!$this->canVoid()) {
                throw new LocalizedException(__('Void action is not available.'));
            }

            $this->initApi();
            $transactionId = str_replace('-void', '', $payment->getTransactionId());

            $transactionsApi = new \SquareConnect\Api\TransactionsApi($this->apiClient);
            $transactionsApi->voidTransaction($locationId, $transactionId);
        } catch (\SquareConnect\ApiException $e) {
            $this->squareupLogger->error($e->__toString());
            $this->_logger->error($e->getMessage());
            $exceptionMessage = $this->parseSquareException($e);
            throw new LocalizedException(__($exceptionMessage));
        } catch (\Exception $e) {
            $this->squareupLogger->error($e->__toString());
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * Initialize required options
     * @return bool
     */
    public function initApi()
    {
        $this->authToken = $this->configHelper->getOAuthToken(true);

        $apiConfig = new \SquareConnect\Configuration();
        $apiConfig->setAccessToken($this->authToken);
        $this->apiClient = new \SquareConnect\ApiClient($apiConfig);

        return true;
    }

    /**
     * Cancel order, voiding the payment to Square
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \Magento\Payment\Model\InfoInterface|Payment
     * @throws \Exception
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this->void($payment);
    }

    /**
     * The real call to Square for authorize or capture
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @param string $type
     * @return $this
     * @throws \Exception
     */
    private function charge(\Magento\Payment\Model\InfoInterface $payment, $amount, $type = 'authorize')
    {
        try {
            $order = $payment->getOrder();

            $store = $order->getStore();
            $storeId = $store->getId();
            $websiteId = $store->getWebsiteId();

            if ($locationId = $this->configHelper->getLocationId('store', $storeId)) {}
            elseif ($locationId = $this->configHelper->getLocationId('website', $websiteId)) {}
            else {
                $locationId = $this->configHelper->getLocationId();
            }

            if (null === $order->getIncrementId()) {
                throw new LocalizedException(__('Order not found.'));
            }

            $shippingAddress = null;
            $billingAddress = null;

            foreach ($order->getAddresses() as $address) {
                if ($address->getAddressType() === 'billing') {
                    $billingAddress = $address;
                } elseif ($address->getAddressType() === 'shipping') {
                    $shippingAddress = $address;
                }
            }

            if (null === $billingAddress) {
                throw new LocalizedException(__('Billing address not found.'));
            }

            if ($shippingAddress ===  false || null === $shippingAddress) {
                $shippingAddress = $billingAddress;
                $this->squareupLogger->info(__('Shipping address not found default to billing address'));
            }

            $customerAddress = $shippingAddress->getStreet();
            $apt = '';

            if (isset($customerAddress[1])) {
                $apt = $customerAddress[1];
            }

            $txRequest = [];
            $txRequest['buyer_email_address'] = $order->getCustomerEmail();

            $txRequest['shipping_address'] = [
                'address_line_1' => $customerAddress[0],
                'address_line_2' => $apt,
                'locality' => $shippingAddress->getCity(),
                'administrative_district_level_1' => $this->dataHelper
                    ->getRegionCodeById($shippingAddress->getRegionId()),
                'postal_code' => $shippingAddress->getPostcode(),
                'country' => $shippingAddress->getCountryId()
            ];

            $customerAddress = $billingAddress->getStreet();
            $apt = '';

            if (isset($customerAddress[1])) {
                $apt = $customerAddress[1];
            }

            $txRequest['billing_address'] = [
                'address_line_1' => $customerAddress[0],
                'address_line_2' => $apt,
                'administrative_district_level_1' => $this->dataHelper
                    ->getRegionCodeById($billingAddress->getRegionId()),
                'locality' => $billingAddress->getCity(),
                'postal_code' => $billingAddress->getPostcode(),
                'country' => $billingAddress->getCountryId()
            ];


            $cardNonce = $payment->getAdditionalInformation('squareup_nonce');
            $buyerVerificationToken = $payment->getAdditionalInformation('buyerVerificationToken');
            if ($payment->getAdditionalInformation('display_form')) {
                if (null === $cardNonce) {
                    throw new LocalizedException(__('Card nonce not found.'));
                }
            }

            $sendAmount = $this->dataHelper->processAmount($amount);

            /* save card on file */
            $customer = $this->customerFactory->create()->load($order->getCustomerId());
            $savedCardId = null;
            if ($payment->getAdditionalInformation('save_square_card') == 1 &&
                $customer && !empty($customer->getSquareupCustomerId())) {
                $this->squareupLogger->info('Saving customer '. $customer->getId() . ' credit card');
                $cardRequest = [
                    'card_nonce' => $cardNonce,
                    'billing_address' => $txRequest['billing_address'],
                    'cardholder_name' => $customer->getFirstname() ." ".$customer->getLastname()
                ];

                $savedCardId = $this->card->sendSaveCard(
                    $customer->getId(),
                    $customer->getSquareSavedCards(),
                    $customer->getSquareupCustomerId(),
                    $cardRequest
                );
            }

            $idempotencyKey = uniqid();
            $txRequest['idempotency_key'] = $idempotencyKey;
            $txRequest['amount_money'] = ['amount' => $sendAmount, 'currency' => $order->getOrderCurrencyCode()];

            $savedCards = json_decode($customer->getSquareSavedCards(), true);
            if (!empty($savedCardId)) {
                $txRequest['customer_card_id'] = $savedCardId;
                $txRequest['customer_id'] = $customer->getSquareupCustomerId();
            } elseif ($this->dataHelper->haveSavedCards() && $this->dataHelper->payedWithSavedCard($cardNonce)) {
                $txRequest['customer_card_id'] = $cardNonce;
                $txRequest['customer_id'] = $customer->getSquareupCustomerId();
            } elseif ($savedCards && array_key_exists($cardNonce, $savedCards)) {
                $txRequest['customer_card_id'] = $cardNonce;
                $txRequest['customer_id'] = $customer->getSquareupCustomerId();
            } else {
                $txRequest['card_nonce'] = $cardNonce;
                if (!$order->getCustomerIsGuest() && !empty($customer->getSquareupCustomerId())) {
                    $txRequest['customer_id'] = $customer->getSquareupCustomerId();
                }
            }

            $txRequest['reference_id'] = 'Confirmation #' . $order->getIncrementId();
            $txRequest['integration_id'] = "sqi_6cf03eb6ac24400ab1e21fbe9d8666b1";
            $txRequest['note'] = 'Magento Order Id #' . $order->getIncrementId();

            // because it is authorization we delay to capture
            $txRequest['delay_capture'] = (self::ACTION_AUTHORIZE_CAPTURE === $type)? false : true;
            $giftCardAmount = 0;
            $giftCards = $this->giftCardCollection->create()
                ->addFieldToFilter('quote_id', ['eq' => $order->getQuoteId()]);
            foreach ($giftCards as $giftCard) {
                //$total += $this->dataHelper->processAmount($giftCard->getAmount());
                $giftCardAmount += $this->dataHelper->processAmount($giftCard->getAmount());
            }

            /* Save order to square */
            if ($this->configHelper->getAllowOrdersSync()) {
                $response = $this->exportOrder->processOrder($order, $giftCardAmount);
                if (!empty($response->getId())) {
                    $this->squareupLogger->info('Order exported.');
                    $txRequest['order_id'] = $response->getId();
                } else {
                    if ($this->configHelper->getApplicationMode() === Mode::PRODUCTION_ENV) {
                        throw new LocalizedException(__('Order was not exported to square.'));
                    }
                }

                if (((int)round($sendAmount) + $giftCardAmount) != $response->getTotalMoney()->getAmount()) {
                    throw new LocalizedException(__('Order grand total is not equal with square order amount'));
                }
            }

            $total = $txRequest['amount_money']['amount'];
            $total += $giftCardAmount;

            $newRequest = [
                'idempotency_key' => $txRequest['idempotency_key'],
                'amount_money' => [
                    'amount' => $total,
                    'currency' => $txRequest['amount_money']['currency']
                ],
                'tenders' => [],
                'delay_capture' => $txRequest['delay_capture'],
                'reference_id' => $txRequest['reference_id'],
                'note' => $txRequest['note'],
//                'customer_id' => $txRequest['customer_id'] ? $txRequest['customer_id'] : null,
                'shipping_address' => $txRequest['shipping_address'],
                'buyer_email_address' => $txRequest['buyer_email_address'],
//                'order_id' => $txRequest['order_id'],
                'integration_id' => $txRequest['integration_id']
            ];

            if (isset($txRequest['customer_id'])) {
                $newRequest['customer_id'] = $txRequest['customer_id'];
            }

            if (isset($txRequest['order_id'])) {
                $newRequest['order_id'] = $txRequest['order_id'];
            }

            if ((int)round($sendAmount) > 0) {
                $data = [
                    'amount_money' => [
                        'amount' => $txRequest['amount_money']['amount'],
                        'currency' => $txRequest['amount_money']['currency']
                    ],
                    'billing_address' => $txRequest['billing_address']
                ];

                if (isset($txRequest['customer_card_id'])) {
                    $data['customer_card_id'] = $txRequest['customer_card_id'];
                } elseif (isset($txRequest['card_nonce'])) {
                    $data['card_nonce'] = $txRequest['card_nonce'];
                    $data['verification_token'] = $buyerVerificationToken;
                }

                $data['note'] = $txRequest['note'];
                $newRequest['tenders'][] = $data;
            }

            $giftCards = $this->giftCardCollection->create()
                ->addFieldToFilter('quote_id', ['eq' => $order->getQuoteId()]);
            foreach ($giftCards as $giftCard) {
                if ((int)$giftCard->getAmount()) {
                    $newRequest['tenders'][] = [
                        'card_nonce' => $giftCard->getCardNonce(),
                        'note' => $txRequest['note'],
                        'verification_token' => $buyerVerificationToken,
                        'amount_money' => [
                            'amount' => $this->dataHelper->processAmount((int)$giftCard->getAmount()),
                            'currency' => $txRequest['amount_money']['currency']
                        ],
                        'billing_address' => $txRequest['billing_address']
                    ];
                }

            }

            $transactionsApi = new \Squareup\Omni\Helper\TransactionsApi($this->apiClient);

            if ($this->configHelper->isGiftCardEnabled()) {
                $this->_logger->error(json_encode($newRequest));
                $apiResponse = $transactionsApi->charge($locationId, json_encode($newRequest));
            } else {
                $txRequest['verification_token'] = $buyerVerificationToken;
                $this->_logger->error(json_encode($txRequest));
                $apiResponse = $transactionsApi->charge($locationId, json_encode($txRequest));
            }

            $transaction = $apiResponse->getTransaction();
            $this->squareupLogger->info($type . ' transaction id# ' . $transaction->getId());

            $payment->setSquareupTransaction($transaction->getId());
            $payment->setTransactionId($transaction->getId());
            $isClosed = (self::ACTION_AUTHORIZE_CAPTURE == $type)? true : false;
            $payment->setIsTransactionClosed($isClosed);
            $this->addCreditCardData($payment, $transaction);
        } catch (\SquareConnect\ApiException $e) {
            $this->squareupLogger->error($e->__toString());
            $this->_logger->error($e->getMessage());
            $exceptionMessage = $this->parseSquareException($e);
            throw new LocalizedException(__($exceptionMessage));
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    private function parseSquareException($e)
    {
        $response = '';

        if (!empty($e->getResponseBody()->errors) && is_array($e->getResponseBody()->errors)) {
            foreach ($e->getResponseBody()->errors as $error) {
                $response .= $error->detail . ' ';
            }
        }

        return $response;
    }

    public function cardSaveOnFile($payment)
    {
        $cardNonce = $payment->getAdditionalInformation('squareup_nonce');
        if ($payment->getAdditionalInformation('display_form')) {
            if (null === $cardNonce) {
                $this->squareupLogger->error(__('Card nonce not found.'));
                throw new LocalizedException(__('Card nonce not found.'));
            }
        }


        $order = $payment->getOrder();
        $customer = $this->customerFactory->create()->load($order->getCustomerId());
        if (null === $customer->getSquareupCustomerId()) {
            $this->squareupLogger->error(__('You must be a register customer in order to place an order.'));
            throw new LocalizedException(__('You must be a register customer in order to place an order.'));
        }

        $address = null;
        foreach ($order->getAddresses() as $a) {
            if ($a->getAddressType() === 'billing') {
                $address = $a;
                break;
            }
        }

        $customerAddress = $address->getStreet();
        $apt = '';

        if (isset($customerAddress[1])) {
            $apt = $customerAddress[1];
        }

        $billingAddress = array(
            'address_line_1' => $customerAddress[0],
            'address_line_2' => $apt,
            'administrative_district_level_1' => $this->dataHelper->getRegionCodeById($address->getRegionId()),
            'locality' => $address->getCity(),
            'postal_code' => $address->getPostcode(),
            'country' => $address->getCountryId()
        );

        $cardRequest = array(
            'card_nonce' => $cardNonce,
            'billing_address' => $billingAddress,
            'cardholder_name' => $customer->getFirstname() ." ".$customer->getLastname()
        );

        try {
            if ($customer->getSquareSavedCards()) {
                $savedCards = json_decode($customer->getSquareSavedCards(), true);

                if (isset($cardRequest['card_nonce']) && array_key_exists($cardRequest['card_nonce'], $savedCards)) {
                    return true;
                }
            }

            $savedCardId = $this->card->sendSaveCard(
                $customer->getId(),
                $customer->getSquareSavedCards(),
                $customer->getSquareupCustomerId(),
                $cardRequest
            );

        } catch (\SquareConnect\ApiException $e) {
            $this->squareupLogger->error($e->__toString());
            throw new LocalizedException(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->squareupLogger->error($e->__toString());
            throw new LocalizedException(__('Error saving card on file.'));
        }

        return $savedCardId;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return true;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param \SquareConnect\Model\Transaction $transaction
     * @return bool
     */
    protected function addCreditCardData(\Magento\Payment\Model\InfoInterface $payment, \SquareConnect\Model\Transaction $transaction)
    {
        $tenders = $transaction->getTenders();
        if(empty($tenders)) {
            return false;
        }

        $tender = $tenders[0];
        $cardDetails = $tender->getCardDetails();
        if(empty($cardDetails)){
            return false;
        }

        $card = $cardDetails->getCard();
        $ccData = [
            'cc_type' => $card->getCardBrand(),
            'cc_last_4' => $card->getLast4(),
            'cc_number' => null,
            'cc_exp_month' => $card->getExpMonth(),
            'cc_exp_year' => $card->getExpYear()
        ];

        $payment->addData($ccData);

        return true;
    }
}
