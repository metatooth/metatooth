<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/21/2018
 * Time: 10:40 AM
 */

namespace Squareup\Omni\Controller\Adminhtml\Transactions;

use Magento\Backend\App\Action;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\TransactionsFactory;
use Squareup\Omni\Model\Refunds\ImportFactory;
use Squareup\Omni\Helper\Data as DataHelper;
use Magento\Store\Model\StoreManagerInterface;

class RefundTransaction extends Action
{
    /**
     * @var DataHelper
     */
    private $dataHelper;
    /**
     * @var \SquareConnect\ApiClient
     */
    private $apiClient;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var TransactionsFactory
     */
    private $transactionsFactory;
    /**
     * @var ImportFactory
     */
    private $importFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * RefundTransaction constructor.
     * @param Action\Context $context
     * @param Logger $logger
     * @param DataHelper $dataHelper
     * @param TransactionsFactory $transactionsFactory
     * @param ImportFactory $importFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Action\Context $context,
        Logger $logger,
        DataHelper $dataHelper,
        TransactionsFactory $transactionsFactory,
        ImportFactory $importFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);

        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
        $this->apiClient = $this->dataHelper->getClientApi();
        $this->transactionsFactory = $transactionsFactory;
        $this->importFactory = $importFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return void
     */
    public function execute()
    {
        $transactionId = $this->getRequest()->getParam('id');
        $api = new \SquareConnect\Api\TransactionsApi($this->apiClient);
        $transaction = $this->transactionsFactory->create()->load($transactionId);
        if (!empty($transaction->getId())) {
            try {
                $idempotencyKey = uniqid();
                $body = [
                    'tender_id' => $transaction->getTenderId(),
                    'amount_money' => [
                        'amount' => $this->dataHelper->processAmount($transaction->getAmount()),
                        'currency' => $this->storeManager->getStore()->getCurrentCurrencyCode()
                    ],
                    'idempotency_key' => $idempotencyKey,
                    'reason' => 'Cancelled order from Magento'
                ];
                $requestData = new \SquareConnect\Model\CreateRefundRequest($body);
                $this->logger->info('refund transaction id# ' . $transaction->getSquareId());
                $response = $api->createRefund(
                    $transaction->getLocationId(),
                    $transaction->getSquareId(),
                    $requestData
                );
                if (empty($response->getErrors()) && !empty($response->getRefund())) {
                    $this->importFactory->create()->saveRefund($response->getRefund());
                }

                $this->messageManager->addSuccess(
                    __('The transaction was successfully refunded.')
                );
                $this->_redirect($this->_redirect->getRefererUrl());
                return;
            } catch (\SquareConnect\ApiException $e) {
                $this->logger->error($e->__toString());
                $errors = $e->getResponseBody()->errors;
                $detail = '';
                foreach ($errors as $error) {
                    $detail = $error->detail;
                }

                $this->messageManager->addError($detail);
                $this->_redirect('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('There was an error refunding this transaction.')
                );
                $this->_redirect('*/*/');
                $this->logger->error($e->__toString());
                return;
            }
        }

        $this->_redirect('*/*/');
    }
}
