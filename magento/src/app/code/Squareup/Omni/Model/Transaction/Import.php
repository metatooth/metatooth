<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/14/2018
 * Time: 4:58 PM
 */

namespace Squareup\Omni\Model\Transaction;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data as DataHelper;
use Squareup\Omni\Helper\Mapping;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\Square;
use Squareup\Omni\Model\TransactionsFactory;
use Squareup\Omni\Model\ResourceModel\Transactions;

class Import extends Square
{
    /**
     * @var \SquareConnect\ApiClient
     */
    private $apiClient;

    /**
     * @var TransactionsFactory
     */
    private $transactionsFactory;

    private $createOrder;
    public $transactionsResource;

    /**
     * Squareup_Omni_Model_Customer_Export_Export constructor.
     * @param Transactions $transactionsResource
     * @param Config $config
     * @param Logger $logger
     * @param DataHelper $dataHelper
     * @param Mapping $mapping
     * @param Context $context
     * @param Registry $registry
     * @param TransactionsFactory $transactionsFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Transactions $transactionsResource,
        CreateOrder $createOrder,
        Config $config,
        Logger $logger,
        DataHelper $dataHelper,
        Mapping $mapping,
        Context $context,
        Registry $registry,
        TransactionsFactory $transactionsFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $logger,
            $dataHelper,
            $mapping,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->apiClient = $this->helper->getClientApi();
        $this->transactionsFactory = $transactionsFactory;
        $this->createOrder = $createOrder;
        $this->transactionsResource = $transactionsResource;
    }

    public function importTransactions($locationId, $beginTime, $cursor = null)
    {
        try {
            $api = new \SquareConnect\Api\TransactionsApi($this->apiClient);
            $response = $api->listTransactions($locationId, $beginTime, null, null, $cursor);
            $transactions = $response->getTransactions();
            $this->logger->info('Location Id: ' . $locationId);
            $this->logger->info('Transaction count: ' . count($transactions));
            $cursor = $response->getCursor();
            if (!empty($transactions)) {
                foreach ($transactions as $transaction) {
                    $this->logger->info('Transaction Id: ' . $transaction->getId());
                    $this->saveTransaction($transaction);
                    $this->createOrder->processTransaction($transaction, $locationId);
                }
            }

            if ($cursor) {
                $this->importTransactions($locationId, $beginTime, $cursor);
            }
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            $this->_logger->error($e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            return false;
        }


        return true;
    }

    /**
     * Save transaction in database
     * @param $transaction
     * @return bool
     */
    public function saveTransaction($transaction)
    {
        try {
            $tenders = $transaction->getTenders();
            $importedTenders = $this->getImportedTenders();
            foreach ($tenders as $tender) {
                if (in_array($tender->getId(), $importedTenders)) {
                    return true;
                }

                $model = $this->transactionsFactory->create();
                $model->setSquareId($transaction->getId())
                    ->setTenderId($tender->getId())
                    ->setLocationId($transaction->getLocationId())
                    ->setCreatedAt($transaction->getCreatedAt());
                $amount = $this->helper->transformAmount($tender->getAmountMoney()->getAmount());
                $processingFee = $this->helper->transformAmount($tender->getProcessingFeeMoney()->getAmount());
                $model->setAmount($amount)
                    ->setProcessingFeeAmount($processingFee);
                if (null !== $transaction->getProduct() && $transaction->getProduct() == 'REGISTER') {
                    $note = 'Transaction from ' . $transaction->getProduct();
                } else {
                    $note = $tender->getNote();
                }

                $model->setNote($note);
                if ($tender->getType() == \Squareup\Omni\Model\Transactions::TYPE_CARD_LABEL) {
                    $model->setType(\Squareup\Omni\Model\Transactions::TYPE_CARD_VALUE)
                        ->setCardBrand($tender->getCardDetails()->getCard()->getCardBrand());
                } elseif ($tender->getType() == \Squareup\Omni\Model\Transactions::TYPE_CASH_LABEL) {
                    $model->setType(\Squareup\Omni\Model\Transactions::TYPE_CASH_VALUE);
                }

                $model->save();
            }

            $this->logger->info(__('Transaction %s was saved.', $transaction->getId()));
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        return true;
    }

    /**
     * Get tenders Id for all transactions from Magento
     *
     * @return array
     */
    public function getImportedTenders()
    {
        $collection = $this->transactionsFactory->create()->getCollection()
            ->addFieldToSelect('tender_id');
        $transactionsTendersIds = [];
        foreach ($collection as $item) {
            $transactionsTendersIds[] = $item->getData('tender_id');
        }

        return $transactionsTendersIds;
    }

    public function singleTransaction($locationId, $transactionId)
    {
        try {
            $api = new \SquareConnect\Api\TransactionsApi($this->apiClient);
            $response = $api->retrieveTransaction($locationId, $transactionId);
            $errors = $response->getErrors();
            $transaction = $response->getTransaction();
            if (empty($errors) && !empty($transaction)) {
                $transactionExists = $this->transactionsResource->transactionExists($locationId, $transactionId);
                if (false !== $transactionExists) {
                    $this->saveTransaction($transaction);
                    $this->createOrder->processTransaction($transaction, $locationId);
                }
            }
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        return true;
    }
}
