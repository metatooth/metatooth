<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/14/2018
 * Time: 10:31 AM
 */

namespace Squareup\Omni\Model\Customer;

use Magento\Framework\Filter\Sprintf;
use Magento\Framework\Model\AbstractModel;
use Magento\Customer\Model\CustomerFactory;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Helper\Data as DataHelper;

class Delete extends AbstractModel
{
    /**
     * @var CustomerFactory
     */
    private $customerFactory;
    /**
     * @var Logger
     */
    private $logger;

    private $apiClient;
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Delete constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param CustomerFactory $customerFactory
     * @param Logger $logger
     * @param DataHelper $dataHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        CustomerFactory $customerFactory,
        Logger $logger,
        DataHelper $dataHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
        $this->dataHelper = $dataHelper;
        $this->apiClient = $this->dataHelper->getClientApi();
    }

    /**
     * Delete Customers from Magento
     * @param $customers
     * @return bool
     */
    public function deleteMagentoCustomers($customers)
    {
        return true;
        try {
            foreach ($customers as $id => $squareId) {
                $this->_registry->register('isSecureArea', true);
                $customer = $this->customerFactory->create()->load($id);
                $customer->setData('deleted_from_square', 1);
                $customer->delete();
                $this->logger->info(sprintf('Customer %s was deleted from Magento by Square sync', $id));
                $this->_registry->unregister('isSecureArea');
            }
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        return true;
    }

    /**
     * Delete Customer from SquareUp app on Magento Customer Delete
     * @param $squareupCustomerId
     * @return bool
     */
    public function deleteSquareupCustomer($squareupCustomerId)
    {
        return true;
        try {
            /* Call SquareUp delete customer method */
            $api = new \SquareConnect\Api\CustomersApi($this->apiClient);
            $api->deleteCustomer($squareupCustomerId);
            $this->logger->info(
                sprintf(__('Customer with SquareUp id:%s was deleted.'), $squareupCustomerId)
            );
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            return false;
        } catch (\Exception $e) {
            $this->_logger->error($e->__toString());
            return false;
        }

        return true;
    }
}
