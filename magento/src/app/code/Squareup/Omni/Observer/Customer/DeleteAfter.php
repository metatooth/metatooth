<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/15/2018
 * Time: 10:30 AM
 */

namespace Squareup\Omni\Observer\Customer;

use Magento\Framework\Event\Observer;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Squareup\Omni\Model\Customer\Delete;

class DeleteAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var Delete
     */
    private $delete;

    /**
     * DeleteAfter constructor.
     * @param ConfigHelper $configHelper
     * @param Delete $delete
     */
    public function __construct(
        ConfigHelper $configHelper,
        Delete $delete
    ) {
        $this->configHelper = $configHelper;
        $this->delete = $delete;
    }

    /**
     * Delete customer from SquareUp app on customer delete action
     * @param Observer $observer
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /* If Sync functionality is disabled, return */
        if (!$this->configHelper->getAllowCustomerSync()) {
            return $this;
        }

        $customer = $observer->getEvent()->getCustomer();
        if ($customer->getData('deleted_from_square')) {
            return $this;
        }

        if (!empty($customer->getSquareupCustomerId())) {
            //$this->delete->deleteSquareupCustomer($customer->getSquareupCustomerId());
        }

        return $this;
    }
}
