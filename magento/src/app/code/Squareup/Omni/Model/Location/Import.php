<?php
/**
 * SquareUp
 *
 * Location Import Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\Location;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Helper\Data;
use SquareConnect\Api\LocationsApi;
use SquareConnect\ApiException;
use Squareup\Omni\Model\LocationFactory;

/**
 * Class Import
 */
class Import extends AbstractModel
{
    /**
     * @var \SquareConnect\ApiClient
     */
    private $apiClient;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var LocationFactory
     */
    private $locationFactory;

    /**
     * Import constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param Logger $logger
     * @param Data $helper
     * @param LocationFactory $locationFactory
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        LocationFactory $locationFactory,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->helper = $helper;

        $this->locationFactory = $locationFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Insert / Update location in database
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function updateLocations()
    {
        try {
            $this->apiClient = $this->helper->getClientApi();
            $api = new LocationsApi($this->apiClient);
            $response = $api->listLocations();
            $locations = $response->getLocations();

            if (!empty($locations)) {
                foreach ($locations as $location) {
                    $this->saveLocation($location);
                }
            }
        } catch (ApiException $e) {
            $this->logger->error($e->__toString());
            throw new LocalizedException(__($e->getMessage()));
        }

        return true;
    }

    /**
     * Save location in database
     *
     * @param $location
     *
     * @return bool
     */
    public function saveLocation($location)
    {
        $ccProcessing = false;
        $isUpdate = true;
        $squareId = $location->getId();
        try {
            $bdLocation = $this->locationFactory->create()->load($squareId, 'square_id');
            if (empty($bdLocation->getData())) {
                $isUpdate = false;
                $bdLocation->setSquareId($squareId);
            }

            $status = 0;
            if ($location->getStatus() == 'ACTIVE') {
                $status = 1;
            }

            if (null !== $location->getCapabilities() && count($location->getCapabilities())) {
                foreach ($location->getCapabilities() as $capability) {
                    if ($capability == 'CREDIT_CARD_PROCESSING') {
                        $ccProcessing = true;
                    }
                }
            }

            $bdLocation->setSquareId($squareId)->setName($location->getName())
                ->setPhoneNumber($location->getPhoneNumber())
                ->setStatus($status)
                ->setCurrency($location->getCurrency())
                ->setCcProcessing($ccProcessing);

            $locationAddress = $location->getAddress();
            if (!empty($locationAddress)) {
                $bdLocation->setAddressLine1($locationAddress->getAddressLine1())
                    ->setLocality($locationAddress->getLocality())
                    ->setAdministrativeDistrictLevel1($locationAddress->getAdministrativeDistrictLevel1())
                    ->setPostalCode($locationAddress->getPostalCode())
                    ->setCountry($locationAddress->getCountry());
            }

            $bdLocation->save();

            $this->logger->info(
                sprintf($isUpdate ? 'Location with ID %s was updated' : 'Location with ID %s was inserted', $squareId)
            );
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        return true;
    }
}
