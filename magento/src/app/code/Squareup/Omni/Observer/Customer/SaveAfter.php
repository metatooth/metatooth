<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/15/2018
 * Time: 10:12 AM
 */

namespace Squareup\Omni\Observer\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Squareup\Omni\Model\Customer\Export;
use Magento\Customer\Model\CustomerFactory;
use Squareup\Omni\Logger\Debugger;

class SaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var Export
     */
    private $export;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;
    /**
     * @var Debugger
     */
    private $debugger;

    /**
     * SaveAfter constructor.
     * @param ConfigHelper $configHelper
     * @param Registry $registry
     * @param Export $export
     * @param CustomerFactory $customerFactory
     * @param Debugger $debugger
     */
    public function __construct(
        ConfigHelper $configHelper,
        Registry $registry,
        Export $export,
        CustomerFactory $customerFactory,
        Debugger $debugger
    ) {
        $this->configHelper = $configHelper;
        $this->export = $export;
        $this->registry = $registry;
        $this->customerFactory = $customerFactory;
        $this->debugger = $debugger;
    }

    /**
     * @param Observer $observer
     * @return SaveAfter
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /* If Sync functionality is disabled, return */
        if (!$this->configHelper->getAllowCustomerSync()) {
            return $this;
        }

        $customer = $observer->getEvent()
            ->getCustomer();
        $this->debugger->info(sprintf('[EXTRA] Save customer with id "%s"', $customer->getId()));
        $customer->load($customer->getId());

        /* If customer is created from import cron send to SquareUp only ReferenceId */
        if ($customer->getSquareupJustImported() != 0) {
            $customer->setData('squareup_just_imported', $customer->getSquareupJustImported() - 1);
            $customer->getResource()->saveAttribute($customer, 'squareup_just_imported');
            return $this;
        }

        /* If customer exists already in Magento and SquareUp then update customer data */
        if (!empty($customer->getOrigData() && $customer->getSquareupCustomerId())) {
            $this->export->updateSquareCustomer($customer->getId(), $customer->getSquareupCustomerId());
            return $this;
        }

        /* Export new customer to SquareUp */
        $this->export->exportNewCustomer($customer);

        $customer->load($customer->getId());

        return $this;
    }
}
