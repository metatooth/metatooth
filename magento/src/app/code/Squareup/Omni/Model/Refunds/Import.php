<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/14/2018
 * Time: 5:11 PM
 */

namespace Squareup\Omni\Model\Refunds;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Helper\Mapping;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\Square;
use Squareup\Omni\Model\RefundsFactory;

class Import extends Square
{
    /**
     * @var \SquareConnect\ApiClient
     */
    private $apiClient;
    /**
     * @var RefundsFactory
     */
    private $refundsFactory;

    /**
     * Import constructor.
     * @param Config $config
     * @param Logger $logger
     * @param Data $helper
     * @param Mapping $mapping
     * @param Context $context
     * @param Registry $registry
     * @param RefundsFactory $refundsFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Config $config,
        Logger $logger,
        Data $helper,
        Mapping $mapping,
        Context $context,
        Registry $registry,
        RefundsFactory $refundsFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
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

        $this->apiClient = $helper->getClientApi();
        $this->refundsFactory = $refundsFactory;
    }

    public function importRefunds($locationId, $beginTime, $cursor = null)
    {
        try {
            $this->logger->info(__('Start import refunds.'));
            $api = new \SquareConnect\Api\TransactionsApi($this->apiClient);
            $response = $api->listRefunds($locationId, $beginTime, null, null, $cursor);
            $refunds = $response->getRefunds();
            $cursor = $response->getCursor();
            if (!empty($refunds)) {
                $importedRefundsIds = $this->getImportedRefunds();
                foreach ($refunds as $refund) {
                    if (in_array($refund->getId(), $importedRefundsIds)) {
                        continue;
                    }

                    $this->saveRefund($refund);
                }
            }
            if ($cursor) {
                $this->importRefunds($locationId, $beginTime, $cursor);
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
     * Save refund in database
     * @param $refund
     * @return bool
     */
    public function saveRefund($refund)
    {
        try {
            $amount = $this->helper->transformAmount($refund->getAmountMoney()->getAmount());
            if (!empty($refund->getProcessingFeeMoney())) {
                $processingFee = $this->helper->transformAmount($refund->getProcessingFeeMoney()->getAmount());
            }

            switch ($refund->getStatus()) {
                case \Squareup\Omni\Model\Refunds::STATUS_PENDING_LABEL:
                    $status = \Squareup\Omni\Model\Refunds::STATUS_PENDING_VALUE;
                    break;
                case \Squareup\Omni\Model\Refunds::STATUS_APPROVED_LABEL:
                    $status = \Squareup\Omni\Model\Refunds::STATUS_APPROVED_VALUE;
                    break;
                case \Squareup\Omni\Model\Refunds::STATUS_REJECTED_LABEL:
                    $status = \Squareup\Omni\Model\Refunds::STATUS_REJECTED_VALUE;
                    break;
                case \Squareup\Omni\Model\Refunds::STATUS_FAILED_LABEL:
                    $status = \Squareup\Omni\Model\Refunds::STATUS_FAILED_VALUE;
                    break;
                default:
                    $status = 0;
                    break;
            }

            $model = $this->refundsFactory->create();
            $model->setSquareId($refund->getId())
                ->setLocationId($refund->getLocationId())
                ->setTransactionId($refund->getTransactionId())
                ->setTenderId($refund->getTenderId())
                ->setCreatedAt($refund->getCreatedAt())
                ->setReason($refund->getReason())
                ->setAmount($amount)
                ->setStatus($status);
            if (!empty($processingFee)) {
                $model->setProcessingFeeAmount($processingFee);
            }

            $model->save();
            $this->logger->info(__('Refund %s was saved.', $refund->getId()));
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        return true;
    }

    /**
     * Get Square Id for all refunds from Magento
     *
     * @return array
     */
    public function getImportedRefunds()
    {
        $collection = $this->refundsFactory->create()->getCollection()
            ->addFieldToSelect('square_id');
        $refundsSquareIds = [];
        foreach ($collection as $item) {
            $refundsSquareIds[] = $item->getData('square_id');
        }

        return $refundsSquareIds;
    }
}
