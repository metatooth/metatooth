<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/14/2018
 * Time: 10:00 AM
 */

namespace Squareup\Omni\Model\Customer;

use Exception;
use Magento\Framework\Model\AbstractModel;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Helper\Data as DataHelper;
use Magento\Store\Model\WebsiteFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\AddressFactory as CustomerAddressFactory;
use Magento\Directory\Model\RegionFactory;
use Psr\Log\LoggerInterface as PsrLogger;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Squareup\Omni\Model\System\Config\Source\Options\Mode;

/**
 * Class Import
 * @package Squareup\Omni\Model\Customer
 */
class Import extends AbstractModel
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
     * @var WebsiteFactory
     */
    private $websiteFactory;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;
    /**
     * @var CustomerAddressFactory
     */
    private $customerAddressFactory;
    /**
     * @var RegionFactory
     */
    private $regionFactory;
    /**
     * @var \Squareup\Omni\Model\Customer\ExportFactory
     */
    private $exportFactory;
    /**
     * @var \Squareup\Omni\Model\Customer\DeleteFactory
     */
    private $deleteFactory;

    /**
     * @var array
     */
    private $squareCustomers = [];
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * Import constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Logger $logger
     * @param DataHelper $dataHelper
     * @param WebsiteFactory $websiteFactory
     * @param CustomerFactory $customerFactory
     * @param CustomerAddressFactory $customerAddressFactory
     * @param RegionFactory $regionFactory
     * @param \Squareup\Omni\Model\Customer\ExportFactory $exportFactory
     * @param \Squareup\Omni\Model\Customer\DeleteFactory $deleteFactory
     * @param ConfigHelper $configHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Logger $logger,
        DataHelper $dataHelper,
        WebsiteFactory $websiteFactory,
        CustomerFactory $customerFactory,
        CustomerAddressFactory $customerAddressFactory,
        RegionFactory $regionFactory,
        ExportFactory $exportFactory,
        DeleteFactory $deleteFactory,
        ConfigHelper $configHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->logHelper = $logger;
        $this->helper = $dataHelper;
        $this->apiClient = $this->helper->getClientApi();
        $this->websiteFactory = $websiteFactory;
        $this->customerFactory = $customerFactory;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->regionFactory = $regionFactory;
        $this->exportFactory = $exportFactory;
        $this->deleteFactory = $deleteFactory;
        $this->configHelper = $configHelper;
    }

    /**
     * List all customers from SquareUp App
     * @return bool
     */
    public function getSquareupCustomers($cursor = null)
    {
        try {
            $api = new \SquareConnect\Api\CustomersApi($this->apiClient);
            $response =  $api->listCustomers($cursor);
            $customers = $response->getCustomers();
            $customersCursor = $response->getCursor();

            // in sandbox mode the returned cursor seems to be the same, and recursion enters an infinite loop
            if ($this->configHelper->getApplicationMode() === Mode::SANDBOX_ENV &&
                $customersCursor === $cursor &&
                $customersCursor !== null) {
                return true;
            }

            if (!empty($customers)) {
                foreach ($customers as $customer) {
                    $this->squareCustomers[] = $customer->getId();
                    $this->importCustomer($customer);
                }
            }

            if (!empty($customersCursor)) {
                $this->getSquareupCustomers($customersCursor);
            }

            /*Check if exists customers to delete from Magento */
            $customersAlreadySync = $this->getCustomersSquareIds();
            $customersToDelete = array_diff($customersAlreadySync, $this->squareCustomers);

            if (!empty($customersToDelete)) {
                /* Delete customers from Magento */
                //$deleteModel = $this->deleteFactory->create();
                //$deleteModel->deleteMagentoCustomers($customersToDelete);
            }
        } catch (\SquareConnect\ApiException $e) {
            $this->logHelper->error($e->__toString());
            $this->_logger->error($e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Save customer from SquareUp in Magento
     *
     * @param $squareupCustomer
     * @return bool
     */
    public function importCustomer($squareupCustomer)
    {
        try {
            $websites = $this->websiteFactory->create()->getCollection()
                ->addFieldToFilter('is_default', 1);
            $website = $websites->getFirstItem();
            $websiteId = $website->getId();

            $customer = $this->customerFactory->create()->setWebsiteId(1);
            ;
            if (!empty($squareupCustomer->getReferenceId())) {
                $customer->load($squareupCustomer->getReferenceId());
            }

            if (empty($customer->getId())) {
                $customer->loadByEmail($squareupCustomer->getEmailAddress());
            }

            if (!empty($customer->getId()) && $squareupCustomer->getUpdatedAt() == $customer->getSquareUpdatedAt()) {
                $this->logHelper->info(
                    __(
                        'No modification for customer Square Id:%1, Magento Id:%2, skip.',
                        $squareupCustomer->getId(),
                        $customer->getId()
                    )
                );
                return true;
            }

            $customer->setWebsiteId($websiteId)
                ->setGroupId(1)
                ->setFirstname($squareupCustomer->getGivenName())
                ->setLastname($squareupCustomer->getFamilyName())
                ->setEmail($squareupCustomer->getEmailAddress())
                ->setSquareupCustomerId($squareupCustomer->getId())
                ->setSquareUpdatedAt($squareupCustomer->getUpdatedAt())
                ->setSquareupJustImported(2);

            if (!empty($squareupCustomer->getNickname())) {
                $customer->setMiddleName($squareupCustomer->getNickname());
            }

            if (empty($squareupCustomer->getCards()) && !empty($customer->getSquareSavedCards())) {
                $customer->setSquareSavedCards(null);
            }

            $customerCards = null;

            if (!empty($squareupCustomer->getCards())) {
                if (!empty($customer->getSquareSavedCards())) {
                    $alreadySavedCards = json_decode($customer->getSquareSavedCards(), true);
                    $apiCards = [];
                    foreach ($squareupCustomer->getCards() as $card) {
                        $apiCards[] = $card->getId();
                        if (array_key_exists($card->getId(), $alreadySavedCards)) {
                            continue;
                        }

                        $alreadySavedCards[$card->getId()] = [
                            'card_brand' => $card->getCardBrand(),
                            'last_4' => $card->getLast4(),
                            'exp_month' => $card->getExpMonth(),
                            'exp_year' => $card->getExpYear(),
                            'cardholder_name' => $card->getCardholderName()
                        ];
                    }

                    $cardsToSave = [];
                    foreach ($apiCards as $apiCard) {
                        $cardsToSave[$apiCard] = $alreadySavedCards[$apiCard];
                    }

                    $customerCards = json_encode($cardsToSave);
                } else {
                    $squareCards = [];
                    foreach ($squareupCustomer->getCards() as $card) {
                        $squareCards[$card->getId()] = [
                            'card_brand' => $card->getCardBrand(),
                            'last_4' => $card->getLast4(),
                            'exp_month' => $card->getExpMonth(),
                            'exp_year' => $card->getExpYear(),
                            'cardholder_name' => $card->getCardholderName()
                        ];
                    }

                    $customerCards = json_encode($squareCards);
                }

                $customer->setSquareSavedCards($customerCards);
            }

            try {
                $customer->save();
            } catch (Exception $ex) {
                $this->logHelper->error($ex->__toString());
                return false;
            }

            try {
                $customer->getResource()->saveAttribute($customer, 'squareup_customer_id');
                $customer->getResource()->saveAttribute($customer, 'square_updated_at');
                $customer->getResource()->saveAttribute($customer, 'squareup_just_imported');
            } catch (Exception $e) {
                $this->logHelper->error($e->__toString());
                return false;
            }

            $squareupAddress = $squareupCustomer->getAddress();
            if (count(array_filter((array)$squareupAddress)) > 2) {
                $addressId = $customer->getData('default_billing');
                $address = $this->customerAddressFactory->create();
                if (!empty($addressId)) {
                    $address->load($addressId);
                }

                $customerAddress = $squareupAddress->getAddressLine1();

                if ($apt = $squareupAddress->getAddressLine2()) {
                    $customerAddress .= "\n" . $apt;
                }
                $address->setCustomerId($customer->getId())
                    ->setFirstname($customer->getFirstname())
                    ->setMiddleName($customer->getMiddlename())
                    ->setLastname($customer->getLastname())
                    ->setCountryId($squareupAddress->getCountry() ? $squareupAddress->getCountry() : 'US')
                    ->setPostcode($squareupAddress->getPostalCode())
                    ->setCity($squareupAddress->getLocality())
                    ->setTelephone($squareupCustomer->getPhoneNumber())
                    ->setStreet($customerAddress)
                    ->setIsDefaultBilling('1')
                    ->setIsDefaultShipping('1')
                    ->setSaveInAddressBook('1');

                if (!empty($squareupAddress->getAdministrativeDistrictlevel1())) {
                    $region = $this->regionFactory->create()
                        ->loadByCode(
                            $squareupAddress->getAdministrativeDistrictlevel1(),
                            $squareupAddress->getCountry() ? $squareupAddress->getCountry() : 'US'
                        );
                    $stateId = $region->getId();
                    $address->setRegionId($stateId);
                }

                $address->save();
            }

            /* If customer is new send to SquareUp ReferenceId */
            if (empty($squareupCustomer->getReferenceId())) {
                $exportModel = $this->exportFactory->create();
                $exportModel->sendSquareupCustomerReferenceId(
                    $customer->getSquareupCustomerId(),
                    $customer->getId()
                );
                $this->logHelper->info(
                    __(
                        'Created received customer from Square $Id:%1, Magento Id:%2',
                        $squareupCustomer->getId(),
                        $customer->getId()
                    )
                );
            } else {
                $this->logHelper->info(
                    __(
                        'Updated received customer from Square Id:%1, Magento Id:%2',
                        $squareupCustomer->getId(),
                        $customer->getId()
                    )
                );
            }
        } catch (Exception $e) {
            $this->logHelper->error($e->__toString());
            return false;
        }

        return true;
    }

    /**
     * Get Square Id for all customers from Magento
     *
     * @return array
     */
    public function getCustomersSquareIds()
    {
        $collection = $this->customerFactory->create()->getCollection()
            ->addAttributeToSelect('squareup_customer_id', 'left')
            ->addAttributeToFilter('squareup_customer_id', ['notnull' => true])
            ->load();

        $customerSquareIds = [];
        foreach ($collection as $item) {
            $customerSquareIds[$item->getId()] = $item->getData('squareup_customer_id');
        }

        return $customerSquareIds;
    }

    /**
     * @param array $squareCustomers
     * @return Import
     */
    public function setSquareCustomers($squareCustomers)
    {
        $this->squareCustomers = $squareCustomers;
        return $this;
    }
}
