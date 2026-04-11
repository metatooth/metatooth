<?php
/**
 * SquareUp
 *
 * CustomerImport Cron
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
use Squareup\Omni\Model\Customer\Import as CustomerImportFactory;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;

/**
 * Class CustomerImport
 */
class CustomerImport extends AbstractCron
{
    /**
     * @var CustomerImportFactory
     */
    private $customerImportFactory;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * CustomerImport constructor.
     * @param Config $config
     * @param CustomerImportFactory $customerImportFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Config $config,
        CustomerImportFactory $customerImportFactory,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($config);

        $this->customerImportFactory = $customerImportFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute($isManual = false)
    {
        if (!$this->configHelper->getAllowCustomerSync()) {
            return false;
        }

        $cronCollection = $this->collectionFactory->create()
            ->addFieldToFilter('job_code', ['eq' => 'customer_import'])
            ->addFieldToFilter('status', ['eq' => 'running']);

        if ($isManual || count($cronCollection) === 1) {
            $this->customerImportFactory->getSquareupCustomers();
        }

        return true;
    }
}
