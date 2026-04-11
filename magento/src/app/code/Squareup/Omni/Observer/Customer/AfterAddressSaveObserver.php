<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 8/1/2018
 * Time: 4:18 PM
 */

namespace Squareup\Omni\Observer\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Squareup\Omni\Helper\Data as Helper;
use Squareup\Omni\Model\Customer\ExportMappingFactory;
use Squareup\Omni\Logger\Logger as LogHelper;
use Psr\Log\LoggerInterface;
use Magento\Directory\Model\RegionFactory;

class AfterAddressSaveObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var ExportMappingFactory
     */
    private $exportMappingFactory;
    /**
     * @var LogHelper
     */
    private $logHelper;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $apiClient;
    /**
     * @var Helper
     */
    private $helper;
    /**
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * AfterAddressSaveObserver constructor.
     * @param ConfigHelper $configHelper
     * @param ExportMappingFactory $exportMappingFactory
     * @param LogHelper $logHelper
     * @param LoggerInterface $logger
     * @param Helper $helper
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        ConfigHelper $configHelper,
        ExportMappingFactory $exportMappingFactory,
        LogHelper $logHelper,
        LoggerInterface $logger,
        Helper $helper,
        RegionFactory $regionFactory
    ) {
        $this->apiClient = $helper->getClientApi();

        $this->configHelper = $configHelper;
        $this->exportMappingFactory = $exportMappingFactory;
        $this->logHelper = $logHelper;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->regionFactory = $regionFactory;
    }

    /**
     * @param Observer $observer
     * @return AfterAddressSaveObserver
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->getAllowCustomerSync()) {
            return $this;
        }

        $customerAddress = $observer->getCustomerAddress();

        if (!$customerAddress->getIsDefaultBilling()) {
            return $this;
        }

        $street = $customerAddress->getStreet();
        $apt = '';

        if (isset($street[1])) {
            $apt = $street[1];
        }

        $customer = $customerAddress->getCustomer();
        $customerData = [
            'given_name' => $customer->getFirstname(),
            'family_name' => $customer->getLastname(),
            'email_address' => $customer->getEmail(),
            'reference_id' => $customer->getId(),
            'note' => 'customer from Magento',
            'phone_number' => $customerAddress->getTelephone(),
            'address' => [
                'address_line_1' => $street[0],
                'address_line_2' => $apt,
                'locality' => $customerAddress->getCity(),
                'administrative_district_level_1' => $customerAddress->getRegionCode(),
                'postal_code' => $customerAddress->getPostcode(),
                'country' => $customerAddress->getCountryId()
            ]
        ];

        if (!empty($customerData)) {
            try {
                /* Call SquareUp update customer method */
                $api = new \SquareConnect\Api\CustomersApi($this->apiClient);
                $response = $api->updateCustomer($customer->getSquareupCustomerId(), $customerData);
                if (empty($response->getCustomer()->getId())) {
                    $this->logHelper->error(
                        __('SquareUp Error: Issue on updating customer %s in SquareUp app.', $customer->getId())
                    );
                    return false;
                }
            } catch (\SquareConnect\ApiException $e) {
                $this->logHelper->error($e->__toString());
                $this->logger->error($e->getMessage());
                return false;
            }
        }
    }
}
