<?php
/**
 * SquareUp
 *
 * Data Helper
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use SquareConnect\Configuration as SquareConfiguration;
use SquareConnect\ApiClient as SquareApiClient;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Squareup\Omni\Model\ResourceModel\Location\CollectionFactory as LocationCollection;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Squareup\Omni\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Registry;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;
use Magento\Framework\App\Cache\Frontend\Pool as CacheFrontendPool;
use Magento\Cron\Model\ScheduleFactory;
use Squareup\Omni\Logger\Logger;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var string
     */
    public $mediaLocation = '/square';

    /**
     * @var RegionFactory
     */
    private $regionFactory;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var File
     */
    private $file;

    /**
     * @var LocationCollection
     */
    private $locationCollection;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var DateTime
     */
    private $datetime;

    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var CacheTypeList
     */
    private $cacheTypeList;
    /**
     * @var CacheFrontendPool
     */
    private $cacheFrontendPool;
    /**
     * @var ScheduleFactory
     */
    private $scheduleFactory;
    /**
     * @var Logger
     */
    protected $log;

    /**
     * Data constructor
     *
     * @param Config $configHelper
     * @param RegionFactory $regionFactory
     * @param DirectoryList $directoryList
     * @param File $file
     * @param LocationCollection $locationCollection
     * @param EavConfig $eavConfig
     * @param ResourceConnection $resourceConnection
     * @param Session $customerSession
     * @param WriterInterface $writer
     * @param DateTime $dateTime
     * @param ProductResource $productResource
     * @param Registry $registry
     * @param Context $context
     * @param CacheTypeList $cacheTypeList
     * @param CacheFrontendPool $cacheFrontendPool
     * @param ScheduleFactory $scheduleFactory
     * @param Logger $log
     */
    public function __construct(
        Config $configHelper,
        RegionFactory $regionFactory,
        DirectoryList $directoryList,
        File $file,
        LocationCollection $locationCollection,
        EavConfig $eavConfig,
        ResourceConnection $resourceConnection,
        Session $customerSession,
        WriterInterface $writer,
        DateTime $dateTime,
        ProductResource $productResource,
        Registry $registry,
        Context $context,
        CacheTypeList $cacheTypeList,
        CacheFrontendPool $cacheFrontendPool,
        ScheduleFactory $scheduleFactory,
        Logger $log
    ) {
        $this->configHelper = $configHelper;
        $this->regionFactory = $regionFactory;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->locationCollection = $locationCollection;
        $this->eavConfig = $eavConfig;
        $this->resourceConnection = $resourceConnection;
        $this->customerSession = $customerSession;
        $this->configWriter = $writer;
        $this->datetime = $dateTime;
        $this->productResource = $productResource;
        $this->registry = $registry;
        parent::__construct($context);
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->scheduleFactory = $scheduleFactory;
        $this->log = $log;
    }

    /**
     * Process amount
     *
     * @param $amount
     * @param string $currency
     *
     * @return float
     */
    public function processAmount($amount)
    {
        return (float)$amount * 100;
    }

    /**
     * Transform amount
     *
     * @param $amount
     * @param string $currency
     *
     * @return float
     */
    public function transformAmount($amount)
    {
        return (float)$amount / 100;
    }

    /**
     * Get client api
     *
     * @return SquareApiClient
     */
    public function getClientApi()
    {
        $authToken = $this->configHelper->getOAuthToken();

        if ($registry = $this->registry->registry('square_oauth_token')) {
            $authToken = $registry;
            $this->registry->unregister('square_oauth_token');
        }

        $apiConfig = new SquareConfiguration();
        $apiConfig->setAccessToken($authToken);
        $apiClient = new SquareApiClient($apiConfig);

        return $apiClient;
    }

    /**
     * Get region code
     *
     * @param $id
     *
     * @return string
     */
    public function getRegionCodeById($id)
    {
        return $this->regionFactory->create()->load($id)->getCode();
    }

    /**
     * Prepare image directory
     *
     * @return true
     */
    public function prepImageDir()
    {
        $path = $this->directoryList->getPath('media') . $this->mediaLocation;
        return $this->file->checkAndCreateFolder($path);
    }

    public function getLocationsOptionArray()
    {
        $optionsArray = [];
        $collection = $this->locationCollection->create();

        if (!empty($collection)) {
            foreach ($collection as $item) {
                $optionsArray[$item->getSquareId()] = $item->getName();
            }
        }

        return $optionsArray;
    }

    /**
     * Reset customer square_id values
     */
    public function resetSquareCustomerFlag()
    {
        $attributeSquareCustomerId = $this->eavConfig->getAttribute('customer', 'squareup_customer_id');
        $attributeSquareUpdatedAt = $this->eavConfig->getAttribute('customer', 'square_updated_at');
        $attributeSquareSavedCards = $this->eavConfig->getAttribute('customer', 'square_saved_cards');

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            $connection->delete(
                $this->resourceConnection->getTableName($attributeSquareCustomerId->getBackendTable()),
                ['attribute_id = ?' => $attributeSquareCustomerId->getAttributeId()]
            );
            $connection->delete(
                $this->resourceConnection->getTableName($attributeSquareUpdatedAt->getBackendTable()),
                ['attribute_id = ?' => $attributeSquareUpdatedAt->getAttributeId()]
            );
            $connection->delete(
                $this->resourceConnection->getTableName($attributeSquareSavedCards->getBackendTable()),
                ['attribute_id = ?' => $attributeSquareSavedCards->getAttributeId()]
            );
            $connection->commit();
        } catch (\Exception $exception) {
            $connection->rollBack();
        }
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLogged()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Check if customer have saved cards
     *
     * @return bool
     */
    public function haveSavedCards()
    {
        if ($this->isCustomerLogged()) {
            $customer = $this->getCustomer();
            if ($customer && !empty($customer->getSquareSavedCards())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get customer
     *
     * @return mixed
     */
    public function getCustomer()
    {
        if (!$this->isCustomerLogged()) {
            return false;
        }

        return $this->customerSession->getCustomer();
    }

    /**
     * Get customer saved cards
     *
     * @return bool|mixed
     */
    public function getCustomerCards()
    {
        $customer = $this->getCustomer();
        if ($customer) {
            $cardSaved = $customer->getSquareSavedCards();
            if (!empty($cardSaved)) {
                return json_decode($cardSaved, true);
            }
        }

        return false;
    }

    /**
     * Create card label
     *
     * @param $card
     *
     * @return string
     */
    public function getCardInputTitle($card)
    {
        $title = $card['cardholder_name'] . ' | ' . $card['card_brand'] . ' | ' . $card['exp_month'] . '/'
            . $card['exp_year'] . ' | **** ' . $card['last_4'];

        return $title;
    }

    /**
     * Check if customer can save cards on file
     *
     * @return bool
     */
    public function canSaveCards()
    {
        if ($this->isCustomerLogged() && !empty($this->getCustomer()->getSquareupCustomerId())) {
            return true;
        }

        return false;
    }

    /**
     * Check if customer payed with a saved card
     *
     * @param $cardId
     *
     * @return bool
     */
    public function payedWithSavedCard($cardId)
    {
        $customer = $this->getCustomer();
        if (!empty($customer) && !empty($customerCards = $this->getCustomerCards())) {
            if (array_key_exists($cardId, $customerCards)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get card on file select options
     * @return mixed
     */
    public function getCardOnFileOption()
    {
        return $this->scopeConfig->getValue('payment/squareup_payment/enable_save_card_on_file');
    }

    /**
     * Check is save on file enabled
     *
     * @return bool
     */
    public function isSaveOnFileEnabled()
    {
        $option = $this->getCardOnFileOption();
        if ($option == \Squareup\Omni\Model\Card::DISALLOW_CARD_ON_FILE) {
            return false;
        }

        return true;
    }

    /**
     * Display Save card checkbox on frontend
     * @return bool
     */
    public function displaySaveCcCheckbox()
    {
        $option = $this->getCardOnFileOption();
        if ($option == \Squareup\Omni\Model\Card::ALLOW_CARD_ON_FILE) {
            return true;
        }

        return false;
    }

    /**
     * Check if only card on file is enabled
     * @return bool
     */
    public function onlyCardOnFileEnabled()
    {
        $option = $this->getCardOnFileOption();
        if ($option == \Squareup\Omni\Model\Card::ALLOW_ONLY_CARD_ON_FILE) {
            return true;
        }

        return false;
    }

    /**
     * Save ranat
     */
    public function saveRanAt($ranAt)
    {
        $this->configHelper->saveRanAt($ranAt);
    }

    public function getProductLocations($id)
    {
        $ids = $this->productResource->getProductLocations($id);
        if (empty($ids) || !in_array($this->configHelper->getLocationId(), $ids)) {
            if (!empty($this->configHelper->getLocationId())) {
                $ids[] = $this->configHelper->getLocationId();
            }
        }

        return $ids;
    }

    /**
     * Create a cron job for a manual start of the cron
     *
     * @param $type
     * @return bool|false|Mage_Core_Model_Abstract
     */
    public function createCronJob($type){
        $ts = strftime('%Y-%m-%d %H:%M:%S', time());
        $job = $this->scheduleFactory->create();
        $job->setData(
            array(
                'job_code' => $type,
                'status' => 'running',
                'messages' => 'Started Manually',
                'created_at' => $ts,
                'scheduled_at' => $ts,
                'executed_at' => $ts
            )
        );

        try {
            $job = $job->save();
        } catch (\Exception $e ) {
            $this->log->info($e->__toString());
            return false;
        }

        return $job;
    }

    /**
     * Finish a job that is started manually
     * @param $job
     * @return bool
     */
    public function finishJob($job)
    {
        try {
            $job->addData(
                array(
                    'status' => 'success',
                    'messages' => 'Manual job finished',
                    'finished_at' => strftime('%Y-%m-%d %H:%M:%S', time())
                )
            );
            $job->save();
        } catch (\Exception $e) {
            $this->log->info($e->__toString());
            return false;
        }

        return true;
    }

    /**
     * Checking if a cron process has a lock on the file to determine if process is actively running
     * @param $type
     * @return bool|resource
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function checkCronJobRunning($type)
    {
        $fh = fopen($this->directoryList->getPath(DirectoryList::VAR_DIR) . DIRECTORY_SEPARATOR . $type, 'w');
        if (!flock($fh, LOCK_EX | LOCK_NB)) {
            return true;
        }

        return $fh;
    }

    /**
     * Clean cron jobs that remained hanged
     *
     * @param $cronCollection
     * @param $currentId
     * @return bool
     */
    public function cleanCronJobs($cronCollection, $currentId)
    {
        foreach ($cronCollection as $job) {
            if ($currentId == $job->getId()) {
                continue;
            }

            try {
                $job->setStatus('failed');
                $job->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()));
                $job->save();
                $this->log->info('Old hanged job was cleared');
            } catch (\Exception $e) {
                $this->log->error($e->__toString());
            }
        }

        return true;
    }
}
