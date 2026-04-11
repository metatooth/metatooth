<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/14/2018
 * Time: 10:23 AM
 */

namespace Squareup\Omni\Model\Customer;

use Magento\Framework\Model\AbstractModel;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Helper\Data as DataHelper;
use Squareup\Omni\Model\Customer\ExportMappingFactory;
use Magento\Customer\Model\CustomerFactory;

/**
 * Class Export
 * @package Squareup\Omni\Model\Customer
 */
class Export extends AbstractModel
{
    /**
     * @var Logger
     */
    private $logHelper;

    /**
     * @var DataHelper
     */
    private $helper;

    /**
     * @var \SquareConnect\ApiClient
     */
    private $apiClient;

    /**
     * @var \Squareup\Omni\Model\Customer\ExportMappingFactory
     */
    private $exportMappingFactory;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * Export constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Logger $logger
     * @param DataHelper $dataHelper
     * @param \Squareup\Omni\Model\Customer\ExportMappingFactory $exportMappingFactory
     * @param CustomerFactory $customerFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Logger $logger,
        DataHelper $dataHelper,
        ExportMappingFactory $exportMappingFactory,
        CustomerFactory $customerFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->logHelper = $logger;
        $this->helper = $dataHelper;
        $this->apiClient = $this->helper->getClientApi();
        $this->exportMappingFactory = $exportMappingFactory;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Export all customers to SquareUp app
     * @return bool
     */
    public function exportCustomers()
    {
        $customersData = $this->exportMappingFactory->create()->getNotExportedCustomers();
        if (empty($customersData)) {
            $this->logHelper->error(__('SquareUp Error: Customers Data for export cron is empty.'));
            return false;
        }

        try {
            foreach ($customersData as $customer) {
                if (is_null($customer)) {
                    continue;
                }
                /* Call SquareUp create customer method */
                $api = new \SquareConnect\Api\CustomersApi($this->apiClient);
                $response = $api->createCustomer($customer);
                $updatedAt = $response->getCustomer()->getUpdatedAt();
                if ($squareCustomerId = $response->getCustomer()->getId()) {
                    $customerObj = $this->customerFactory->create()->load($customer['reference_id']);
                    /* Save squareup_customer_id attribute */
                    $this->saveSquareupIdAttribute($customerObj, $squareCustomerId, $updatedAt);
                }
            }
        } catch (\SquareConnect\ApiException $e) {
            $this->logHelper->error($e->__toString());
            $this->_logger->error($e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            return false;
        }

        return true;
    }

    public function sendSquareupCustomerReferenceId($squareupId, $referenceId)
    {
        $customerData = [
            'reference_id' => $referenceId,
        ];
        try {
            /* Call SquareUp update customer method */
            $api = new \SquareConnect\Api\CustomersApi($this->apiClient);
            $response = $api->updateCustomer($squareupId, $customerData);
            if (empty($response->getCustomer()->getId())) {
                $this->logHelper->error(
                    __('SquareUp Error: Issue on updating customer %s in SquareUp app.', $referenceId)
                );
                return false;
            }
        } catch (\SquareConnect\ApiException $e) {
            $this->logHelper->error($e->__toString());
            $this->_logger->error($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Save SquareUp id to customer obj
     * @param $customer
     * @param $squareupId
     * @return bool
     */
    public function saveSquareupIdAttribute($customer, $squareupId, $updatedAt)
    {
        try {
            $customer->setData('squareup_customer_id', $squareupId);
            $customer->setData('square_updated_at', $updatedAt);
            $customer->getResource()->saveAttribute($customer, 'squareup_customer_id');
            $customer->getResource()->saveAttribute($customer, 'square_updated_at');
        } catch (\Exception $e) {
            $this->logHelper->error($e->__toString());
            $this->_logger->error($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Update customer on SquareUp app
     * @param $customerId
     * @param $squareId
     * @return bool
     */
    public function updateSquareCustomer($customerId, $squareId)
    {
        $customerData = $this->exportMappingFactory->create()->processCustomer($customerId);
        if (is_null($customerData) || (empty($customerData) && empty($squareId))) {
            $this->logHelper->error(
                __('SquareUp Error: Customer Data for update customer $s is empty', $customerId)
            );
            return false;
        }

        try {
            /* Call SquareUp update customer method */
            $api = new \SquareConnect\Api\CustomersApi($this->apiClient);
            $response = $api->updateCustomer($squareId, $customerData);
            if (empty($response->getCustomer()->getId())) {
                $this->logHelper->error(
                    __('SquareUp Error: Issue on updating customer %s in SquareUp app.', $customerId)
                );
                return false;
            }
            $this->logHelper->info(__('Customer %s was updated in SquareUp app.', $customerId));
        } catch (\SquareConnect\ApiException $e) {
            $this->logHelper->error($e->__toString());
            $this->_logger->error($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Export new customer to SquareUp app
     * @param $customer
     * @return bool
     */
    public function exportNewCustomer($customer)
    {
        $customerData = $this->exportMappingFactory->create()->mapNewCustomer($customer);
        if (empty($customerData) || is_null($customerData)) {
            $this->logHelper->error(__('SquareUp Error: Customer Data for new customer is empty'));
            return false;
        }

        try {
            /* Call SquareUp create customer method */
            $api = new \SquareConnect\Api\CustomersApi($this->apiClient);
            $response = $api->createCustomer($customerData);
            $updatedAt = $response->getCustomer()->getUpdatedAt();
            if ($squareCustomerId = $response->getCustomer()->getId()) {
                /* Save squareup_customer_id attribute */
                $this->saveSquareupIdAttribute($customer, $squareCustomerId, $updatedAt);
            }
        } catch (\SquareConnect\ApiException $e) {
            $this->logHelper->error($e->__toString());
            $this->_logger->error($e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            return false;
        }

        return true;
    }
}
