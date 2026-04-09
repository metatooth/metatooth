<?php
/**
 * SquareUp
 *
 * CustomerExport Cron
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Cron;

use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Model\Customer\Export as CustomerExportFactory;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;

/**
 * Class CustomerExport
 */
class CustomerExport extends AbstractCron
{
    /**
     * @var CustomerExportFactory
     */
    private $customerExportFactory;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * CustomerExport constructor.
     * @param Config $config
     * @param CustomerExportFactory $customerExportFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Config $config,
        CustomerExportFactory $customerExportFactory,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($config);

        $this->customerExportFactory = $customerExportFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute($isManual = false)
    {
        if (!$this->configHelper->getAllowCustomerSync()) {
            return false;
        }

        $cronCollection = $this->collectionFactory->create()
            ->addFieldToFilter('job_code', ['eq' => 'customer_export'])
            ->addFieldToFilter('status', ['eq' => 'running']);

        if ($isManual || count($cronCollection) === 1) {
            $this->customerExportFactory->exportCustomers();
        }

        return true;
    }
}
