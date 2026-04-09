<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/14/2018
 * Time: 10:51 AM
 */

namespace Squareup\Omni\Model\Customer;

use Magento\Framework\Model\AbstractModel;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Customer\Model\AddressFactory;
use Magento\Directory\Model\RegionFactory;

class ExportMapping extends AbstractModel
{
    /**
     * @var CustomerFactory
     */
    private $customerFactory;
    /**
     * @var Iterator
     */
    private $iterator;
    /**
     * @var AddressFactory
     */
    private $addressFactory;
    /**
     * @var RegionFactory
     */
    private $regionFactory;
    /**
     * @var
     */
    private $customerData;

    /**
     * ExportMapping constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param CustomerFactory $customerFactory
     * @param Iterator $iterator
     * @param AddressFactory $addressFactory
     * @param RegionFactory $regionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        CustomerFactory $customerFactory,
        Iterator $iterator,
        AddressFactory $addressFactory,
        RegionFactory $regionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->customerFactory = $customerFactory;
        $this->iterator = $iterator;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
    }

    /**
     * Return an array with customers response from SquareUp
     * @return array
     */
    public function getNotExportedCustomers()
    {
        $collection = $this->customerFactory->create()->getCollection()
            ->addAttributeToSelect('squareup_customer_id', 'left')
            ->addAttributeToFilter('squareup_customer_id', ['null' => true])
            ->load();

        $this->iterator->walk(
            $collection->getSelect(),
            [[$this, 'processCustomer']]
        );

        return $this->customerData;
    }

    /**
     * Process Customer
     * @param $args
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processCustomer($args)
    {
        if (is_array($args)) {
            $customerId = $args['row']['entity_id'];
        } else {
            $customerId = $args;
        }

        $customer = $this->customerFactory->create()->load($customerId);
        $address = $customer->getPrimaryBillingAddress();
        if ($customer && $customer->getId()) {
            if (empty($address)) {
                $address = $this->addressFactory->create()->load($customer->getDefaultBilling());
            }

            $customerAddress = $address->getStreet();
            $apt = '';

            if (isset($customerAddress[1])) {
                $apt = $customerAddress[1];
            }

            $customerData = [
                'given_name' => $customer->getFirstname(),
                'family_name' => $customer->getLastname(),
                'email_address' => $customer->getEmail(),
                'reference_id' => $customer->getId(),
                'note' => !empty($customer->getNote()) ? $customer->getNote() : 'customer from Magento'
            ];
            if (!empty($address) && count($address->getData()) > 1) {
                $region = $this->regionFactory->create()->load($address->getRegionId());
                $customerData['address'] = [
                    'address_line_1' => $customerAddress[0],
                    'address_line_2' => $apt,
                    'locality' => $address->getCity(),
                    'administrative_district_level_1' => $region->getCode(),
                    'postal_code' => $address->getPostcode(),
                    'country' => $address->getCountryId()
                ];
                $customerData['phone_number'] = $address->getTelephone();
                $digits = preg_match_all( "/[0-9]/", $address->getTelephone());

                if ($digits < 9 || $digits > 16) {
                    return null;
                }
            }

            if (!is_array($args)) {
                return $customerData;
            }

            $this->customerData[] = $customerData;
        }
    }

    /**
     * Map not processed customer
     * @param $customer
     * @return array
     */
    public function mapNewCustomer($customer)
    {
        $customerData = [
            'given_name' => $customer->getFirstname(),
            'family_name' => $customer->getLastname(),
            'email_address' => $customer->getEmail(),
            'reference_id' => $customer->getId(),
            'note' => 'customer from Magento'
        ];

        return $customerData;
    }
}
