<?php
namespace Squareup\Omni\Model\Transaction;

use Magento\Framework\Exception\LocalizedException;
use Squareup\Omni\Model\Square;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Helper\Mapping;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Api;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Customer\Model\AddressFactory as CustomerAddressFactory;

class CreateOrder extends Square
{
    /**
     * @var mixed
     */
    private $websiteId;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    private $store;

    /**
     * @var \SquareConnect\Api\CustomersApi
     */
    private $customerApi;

    /**
     * @var \SquareConnect\Api\V1TransactionsApi
     */
    private $transactionApi;

    /**
     * @var \SquareConnect\Model\Customer
     */
    private $squareCustomer;

    /**
     * @var \SquareConnect\Model\Transaction
     */
    private $transaction;

    /**
     * @var \SquareConnect\Model\V1Payment
     */
    private $payment;

    /**
     * @var array
     */
    private $products = [];

    /**
     * @var \Magento\Customer\Model\Customer
     */
    private $customer;

    /**
     * @var float
     */
    private $grandTotal;

    /**
     * @var float
     */
    private $taxAmount;

    /**
     * @var float
     */
    private $subtotal;

    /**
     * @var float
     */
    private $shippingAmount;

    private $storeManager;

    private $customerFactory;

    private $productFactory;

    private $orderFactory;

    private $cartManagementInterface;

    private $cartRepositoryInterface;

    private $customerRepository;

    private $regionFactory;

    private $invoiceService;

    private $dbTransaction;

    private $creditmemoFactory;

    private $creditmemoService;

    protected $_stockState;

    protected $_updatedStocks;

    /**
     * @var CustomerAddressFactory
     */
    private $customerAddressFactory;

    public function __construct(
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        ProductFactory $productFactory,
        OrderFactory $orderFactory,
        Api\CartRepositoryInterface $cartRepository,
        Api\CartManagementInterface $cartManagement,
        CustomerRepositoryInterface $customerRepository,
        RegionFactory $regionFactory,
        InvoiceService $invoiceService,
        Transaction $dbTransaction,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        CustomerAddressFactory $customerAddressFactory,
        Config $config,
        Logger $logger,
        Data $helper,
        Mapping $mapping,
        Context $context,
        Registry $registry,
        $resource = null,
        $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $logger,
            $helper,
            $mapping,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->storeManager = $storeManager;
        $this->store = $storeManager->getDefaultStoreView();
        $this->websiteId = $this->store->getWebsiteId();

        $this->customerApi = new \SquareConnect\Api\CustomersApi($this->helper->getClientApi());
        $this->transactionApi = new \SquareConnect\Api\V1TransactionsApi($this->helper->getClientApi());
        $this->customerFactory = $customerFactory;
        $this->productFactory = $productFactory;
        $this->orderFactory = $orderFactory;
        $this->cartRepositoryInterface = $cartRepository;
        $this->cartManagementInterface = $cartManagement;
        $this->customerRepository = $customerRepository;
        $this->regionFactory = $regionFactory;
        $this->invoiceService = $invoiceService;
        $this->dbTransaction = $dbTransaction;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->_stockState = $stockState;
        $this->customerAddressFactory = $customerAddressFactory;
    }

    /**
     * @param \SquareConnect\Model\Transaction $transaction
     *
     * @return void
     */
    public function processTransaction($transaction)
    {
        if (!$this->configHelper->isConvertTransactionsEnabled()) {
            return;
        }

        try {
            foreach ($transaction->getTenders() as $tender) {
                $this->products = [];
                $this->transaction = $transaction;

                if ($this->checkCustomer($tender->getCustomerId()) &&
                    $this->checkItems($tender->getId()) &&
                    $this->checkOrder($transaction->getId())
                ) {
                    $this->createOrder();
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Create new customer.
     *
     * @return bool
     */
    private function createCustomer()
    {
        $address = $this->squareCustomer->getAddress();

        if ($address) {
            $firstName = $address->getFirstName() ? $address->getFirstName() : $this->squareCustomer->getFamilyName();
            $lastName = $address->getLastName() ? $address->getLastName() : $this->squareCustomer->getGivenName();
        } else {
            $firstName = $this->squareCustomer->getFamilyName();
            $lastName = $this->squareCustomer->getGivenName();
        }

        $customer = $this->customerFactory->create()
            ->setWebsiteId($this->websiteId)
            ->setStore($this->store)
            ->setFirstname($lastName)
            ->setLastname($firstName)
            ->setEmail($this->squareCustomer->getEmailAddress())
            ->setSquareupCustomerId($this->squareCustomer->getId())
            ->setSquareUpdatedAt($this->squareCustomer->getUpdatedAt())
            ->setSquareupJustImported(2);

        try {
            $this->customer = $customer->save();
            $customer->getResource()->saveAttribute($customer, 'squareup_customer_id');
            $customer->getResource()->saveAttribute($customer, 'square_updated_at');
            $customer->getResource()->saveAttribute($customer, 'squareup_just_imported');
        } catch (\Exception $exception) {
            $this->logger->error("Could not create new customer.");
            return false;
        }


        $squareupAddress = $this->squareCustomer->getAddress();
        if (count(array_filter((array)$squareupAddress)) > 2) {
            $addressId = $customer->getData('default_billing');
            $address = $this->customerAddressFactory->create();
            if (!empty($addressId)) {
                $address->load($addressId);
            }

            $country = $squareupAddress->getCountry();

            if (!$country) {
                $country = 'US';
            }

            $customerAddress = $squareupAddress->getAddressLine1();

            if ($apt = $squareupAddress->getAddressLine2()) {
                $customerAddress .= "\n" . $apt;
            }

            $address->setCustomerId($customer->getId())
                ->setFirstname($customer->getFirstname())
                ->setMiddleName($customer->getMiddlename())
                ->setLastname($customer->getLastname())
                ->setCountryId($country)
                ->setPostcode($squareupAddress->getPostalCode())
                ->setCity($squareupAddress->getLocality())
                ->setTelephone($this->squareCustomer->getPhoneNumber())
                ->setStreet($customerAddress)
                ->setIsDefaultBilling('1')
                ->setIsDefaultShipping('1')
                ->setSaveInAddressBook('1');

            if (!empty($squareupAddress->getAdministrativeDistrictlevel1())) {
                $region = $this->regionFactory->create()
                    ->loadByCode(
                        $squareupAddress->getAdministrativeDistrictlevel1(),
                        $country
                    );
                $stateId = $region->getId();
                $address->setRegionId($stateId);
            }

            try {
                $address->save();
            } catch (\Exception $exception) {
                $this->logger->error('Could not add address to customer.');
                return false;
            }
        }

        return true;
    }

    /**
     * Check transaction customer.
     *
     * @param string $customerId
     *
     * @return bool
     */
    private function checkCustomer($customerId)
    {
        if (!$customerId) {
            return false;
        }

        $customer = $this->customerApi->retrieveCustomer($customerId);

        if (!$customer->getErrors()) {
            $this->squareCustomer = $customer->getCustomer();

            if (!$this->squareCustomer->getEmailAddress()) {
                $this->logger->error('Unable to create order because the customer email is missing');
                return false;
            }

            if (!$this->squareCustomer->getAddress()) {
                $this->logger->error('Unable to create order because the customer address is missing');
                return false;
            }

            try {
                $this->customer = $this->customerFactory->create();
                $this->customer->setWebsiteId($this->websiteId);
                $this->customer->loadByEmail($this->squareCustomer->getEmailAddress());

                if ($this->customer->getId()) {
                    return true;
                } else {
                    if ($this->createCustomer()) {
                        return true;
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return false;
    }

    /**
     * Check transaction items.
     *
     * @param string $paymentId
     *
     * @return bool
     */
    private function checkItems($paymentId)
    {
        if (!$paymentId) {
            return false;
        }

        $this->payment = $this->transactionApi->retrievePayment($this->transaction->getLocationId(), $paymentId);
        $itemizations = $this->payment->getItemizations();
        $counter = $this->shippingAmount = 0;

        foreach ($itemizations as $itemization) {
            if ($sku = $itemization->getItemDetail()->getSku()) {
                $product = $this->productFactory->create();

                if (!$productId = $product->getIdBySku($sku)) {
                    $this->logger->info(
                        'Order was not created because there were missing items inside transaction ' .
                        $paymentId
                    );

                    return false;
                }

                $product = $product->load($productId);
                $price = number_format(($itemization->getGrossSalesMoney()->getAmount() /
                        $itemization->getQuantity()) / 100, 4);
                $product->setPrice((double)$price);
                $this->products[$product->getId()] = [
                    'quantity' => $itemization->getQuantity(),
                    'model' => $product,
                ];

                $this->prepareProductTax($product->getId(), $itemization);
            } else {
                $this->shippingAmount = $itemization->getTotalMoney()->getAmount() / 100;
                $counter++;
            }
        }

        if ($counter == count($itemizations)) {
            $this->logger->info('Order was not created because there were missing items inside transaction ' .
                $paymentId);

            return false;
        }

        return true;
    }

    /**
     * Prepare product tax.
     *
     * @param int|string $productId
     * @param $itemization
     *
     * @return void
     */
    private function prepareProductTax($productId, $itemization)
    {
        $percent = $amount = 0;

        foreach ($itemization->getTaxes() as $tax) {
            $percent += $tax->getRate();
            $amount += $tax->getAppliedMoney()->getAmount() / 100;
        }

        $this->products[$productId]['tax'] = [
            'percent' => $percent,
            'amount' => $amount,
        ];
    }

    /**
     * Check if order with transaction id exists.
     *
     * @param string $transactionId
     *
     * @return bool
     */
    private function checkOrder($transactionId)
    {
        $order = $this->orderFactory->create()
            ->load($transactionId, 'square_order_id');

        if ($order->getEntityId()) {
            sprintf('Transaction %s was already converted. Order Id %s', $transactionId, $order->getEntityId());

            return false;
        }

        return true;
    }

    /**
     * Create order.
     *
     * @return void
     */
    private function createOrder()
    {
        try {
            if ($this->customer) {
                $this->customer->setGroupId(null);
            }

            $billingAddress = [];
            $shippingMethod = 'square_shipping_square_shipping';
            $paymentMethod = 'squareup_transaction_payment';
            $address = $this->squareCustomer->getAddress();

            $cartId = $this->cartManagementInterface->createEmptyCart();
            $quote = $this->cartRepositoryInterface->get($cartId);
            $quote->setStore($this->store);

            if ($this->customer) {
                $quote->assignCustomer($this->customer->getDataModel());
            }

            foreach ($this->products as $productData) {
                $qty = $this->_stockState->getStockQty($productData['model']->getId(), $productData['model']->getStore()->getWebsiteId());
                $productData['model']->setStockData(array(
                    'use_config_manage_stock' => 1,
                    'is_in_stock' => 1,
                    'qty' => $qty + $productData['quantity'],
                    'manage_stock' => 1
                ));

                $productData['model']->save();
                $productData['model']->setData('salable', true);
                $quote->addProduct($productData['model'], $productData['quantity']);
            }

            if ($address) {
                $country = $address->getCountry();

                if (!$country) {
                    $country = 'US';
                }

                $regionId = $this->regionFactory->create()
                    ->loadByCode($address->getAdministrativeDistrictLevel1(), $country);

                $billingAddress = [
                    'lastname' => $address->getFirstName() ? $address->getFirstName() :
                        $this->squareCustomer->getFamilyName(),
                    'firstname' => $address->getLastName() ? $address->getLastName() :
                        $this->squareCustomer->getGivenName(),
                    'company' => $address->getOrganization(),
                    'street' => $address->getAddressLine1() . ' ' . $address->getAddressLine2(),
                    'city' => $address->getLocality(),
                    'country_id' => $country,
                    'postcode' => $address->getPostalCode(),
                    'telephone' => $this->squareCustomer->getPhoneNumber() ? $this->squareCustomer->getPhoneNumber() :
                        '1234567890',
                    'region_id' => $regionId->getId()
                ];
            }

            //Set Address to quote
            $quote->getBillingAddress()->addData($billingAddress);
            $quote->getShippingAddress()->addData($billingAddress);

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                ->setShippingMethod($shippingMethod);

            $quote->setPaymentMethod($paymentMethod);
            $quote->setCustomerEmail($this->squareCustomer->getEmailAddress());
            $quote->setCustomerIsGuest(true);

            foreach ($quote->getAddressesCollection() as $address) {
                if ($rates = $address->getShippingRatesCollection()) {
                    $items = $rates->getItems();
                    foreach ($items as $item) {
                        $item->setPrice($this->shippingAmount);
                    }
                }
            }

            $quote->getPayment()->importData(['method' => $paymentMethod]);
            $quote->collectTotals();
            $quote->save();
            $this->updateQuote($quote);

            $quote = $this->cartRepositoryInterface->get($quote->getId());
            $orderId = $this->cartManagementInterface->placeOrder($quote->getId());
            $order = $this->orderFactory->create()->load($orderId);

            $this->updateOrder($order);

            $order->setSquareOrderId($this->transaction->getId())->save();
            $order->setEmailSent(0);
            $this->createInvoice($order);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     */
    private function updateQuote($quote)
    {
        $this->grandTotal = $this->taxAmount = $this->subtotal = 0;

        foreach ($quote->getItemsCollection() as $item) {
            $tax = $this->products[$item->getProductId()]['tax'];

            $item->addData([
                'tax_percent' => $tax['percent'],
                'tax_amount' => $tax['amount'],
                'base_tax_amount' => $tax['amount'],
                'price_incl_tax' => $item->getPrice() + $tax['amount'],
                'base_price_incl_tax' => $item->getPrice() + $tax['amount'],
                'row_total_incl_tax' => ($item->getPrice() * $item->getQty()) + $tax['amount'],
                'base_row_total_incl_tax' => ($item->getPrice() * $item->getQty()) + $tax['amount']
            ])->save();

            $this->grandTotal += $item->getRowTotalInclTax();
            $this->taxAmount += $tax['amount'];
        }

        $this->subtotal = $this->grandTotal - $this->taxAmount;

        $quote->addData([
            'grand_total' => $this->grandTotal,
            'base_grand_total' => $this->grandTotal,
            'subtotal' => $this->subtotal,
            'base_subtotal' => $this->subtotal,
            'subtotal_with_discount' => $this->subtotal,
            'base_subtotal_with_discount' => $this->subtotal
        ])->save();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    private function updateOrder($order)
    {
        $order->addData([
            'base_grand_total' => $this->grandTotal,
            'grand_total' => $this->grandTotal,
            'base_subtotal' => $this->subtotal,
            'subtotal' => $this->subtotal,
            'base_tax_amount' => $this->taxAmount,
            'tax_amount' => $this->taxAmount,
            'base_subtotal_incl_tax' => $this->taxAmount + $this->subtotal,
            'subtotal_incl_tax' => $this->taxAmount + $this->subtotal
        ]);

        if ($this->transaction->getProduct() == 'REGISTER') {
            $order->addStatusHistoryComment('Transaction from register');
        }

        $order->save();

        foreach ($order->getAllItems() as $item) {
            $product = $this->products[$item->getProductId()]['model'];

            $item->addData([
                'price' => $product->getPrice(),
                'base_price' => $product->getPrice(),
                'original_price' => $product->getPrice(),
                'base_original_price' => $product->getPrice(),
                'row_total' => $item->getQtyOrdered() * $product->getPrice(),
                'base_row_total' => $item->getQtyOrdered() * $product->getPrice(),
                'price_incl_tax' => (double)$product->getPrice() +
                    (double)$this->products[$item->getProductId()]['tax'],
                'base_price_incl_tax' => (double)$product->getPrice() +
                    (double)$this->products[$item->getProductId()]['tax']
            ])->save();
        }
    }

    private function createInvoice($order)
    {
        if (!$order->canInvoice()) {
            throw new LocalizedException(__('Cannot create an invoice.'));
        }

        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
        $invoice->save();

        $order = $invoice->getOrder()->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
        $order->addStatusHistoryComment(
            sprintf('Square POS - Magento Order Id %s', $this->transaction->getId())
        );

        $this->dbTransaction
            ->addObject($invoice)
            ->addObject($order)
            ->save();

        if ($this->transaction->getRefunds()) {
            $this->createCreditMemo($invoice);
        }
    }

    private function createCreditMemo($invoice)
    {
        $creditmemo = $this->creditmemoFactory->createByOrder($invoice->getOrder());
        $this->creditmemoService->refund($creditmemo);
    }
}
