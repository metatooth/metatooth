<?php
/**
 * SquareUp
 *
 * Inventory Adminhtml Controller
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Controller\Adminhtml\Giftcard;

use Magento\Backend\App\Action;
use Squareup\Omni\Model\ResourceModel\GiftCardRefund\CollectionFactory as GiftCardRefundCollection;
use Squareup\Omni\Model\ResourceModel\GiftCardRefund as GiftCardRefundResource;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;
use Squareup\Omni\Model\ResourceModel\GiftCard as GiftCardResource;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Squareup\Omni\Helper\Data as DataHelper;
use Magento\Framework\Exception\LocalizedException;

class Refund extends Action
{
    private $apiClient;

    private $giftCardRefundCollection;

    private $giftCardRefundResource;

    private $giftCardCollection;

    private $giftCardResource;

    private $orderFactory;

    private $orderResource;

    private $configHelper;

    private $dataHelper;

    public function __construct(
        Action\Context $context,
        GiftCardRefundCollection $giftCardRefundCollection,
        GiftCardRefundResource $giftCardRefundResource,
        GiftCardCollection $giftCardCollection,
        GiftCardResource $giftCardResource,
        OrderFactory $orderFactory,
        OrderResource $orderResource,
        ConfigHelper $configHelper,
        DataHelper $dataHelper
    ){
        parent::__construct($context);

        $this->giftCardRefundCollection = $giftCardRefundCollection;
        $this->giftCardRefundResource = $giftCardRefundResource;
        $this->giftCardCollection = $giftCardCollection;
        $this->giftCardResource = $giftCardResource;
        $this->orderFactory = $orderFactory;
        $this->orderResource = $orderResource;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        $giftcardData = [];
        $giftCards = $this->getRequest()->getParam('giftCards');
        $giftCards = json_decode($giftCards);
        foreach ($giftCards as $item) {
            $giftcardData[$item->entity_id] = $item->amount;
        }

        $orderId = $this->getRequest()->getParam('order_id');
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        $order = $this->orderFactory->create();
        $this->orderResource->load($order, $orderId);
        $payment = $order->getPayment();

        $giftCardCollection = $this->giftCardCollection->create()
            ->addFieldToFilter('entity_id', array('in' => array_keys($giftcardData)));
        foreach ($giftCardCollection as $giftCard) {
            if ($giftCard->getAmount() < $giftcardData[$giftCard->getEntityId()]) {
                throw new LocalizedException(__('message'));
            }
        }

        try {
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
            $giftCardCodes = [];
            foreach ($transaction->getTenders() as $tender) {
                if ('SQUARE_GIFT_CARD' == $tender->getCardDetails()->getCard()->getCardBrand()) {
                    $giftCardCodes[$tender->getId()] = '**** **** **** ' . $tender->getCardDetails()->getCard()->getLast4();
                }
            }

            foreach ($giftCardCollection as $item) {
                $tenderId = array_search($item->getCardCode(), $giftCardCodes);

                if ($tenderId) {
                    $data = [
                        'tender_id' => $tenderId,
                        'amount_money' => [
                            'amount' => $this->dataHelper->processAmount($giftcardData[$item->getEntityId()]),
                            'currency' => $order->getOrderCurrencyCode()
                        ],
                        'idempotency_key' =>  uniqid(),
                        'reason' => 'Refund order #' . $order->getIncrementId() . ' from location #' . $locationId
                    ];
                }


                $transactionsApi->createRefund($locationId, $payment->getSquareupTransaction(), json_encode($data));
                $item->setAmount($item->getAmount() - $giftcardData[$item->getEntityId()]);
                $this->giftCardResource->save($item);
            }
        } catch (\SquareConnect\ApiException $e) {
            $exceptionMessage = $this->parseSquareException($e);
            throw new LocalizedException(__($e));
        } catch (\Exception $e) {

        }

        $url = $this->getUrl('sales/order_invoice/view', ['invoice_id' => $invoiceId]);
        $this->getResponse()->setBody($url);
    }

    public function initApi()
    {
        $authToken = $this->configHelper->getOAuthToken(true);

        $apiConfig = new \SquareConnect\Configuration();
        $apiConfig->setAccessToken($authToken);
        $this->apiClient = new \SquareConnect\ApiClient($apiConfig);

        return true;
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
}
