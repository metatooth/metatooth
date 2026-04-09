<?php
/**
 * SquareUp
 *
 * Inventory Cron
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Cron;

use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Model\Inventory as InventoryModel;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Squareup\Omni\Logger\Logger;

/**
 * Class Inventory
 */
class Inventory extends AbstractCron
{
    private $inventoryModel;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var Data
     */
    private $data;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Inventory constructor.
     * @param Config $config
     * @param InventoryModel $inventoryModel
     * @param CollectionFactory $collectionFactory
     * @param Data $data
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        InventoryModel $inventoryModel,
        CollectionFactory $collectionFactory,
        Data $data,
        Logger $logger
    ) {
        $this->inventoryModel = $inventoryModel;
        parent::__construct($config);
        $this->collectionFactory = $collectionFactory;
        $this->data = $data;
        $this->logger = $logger;
    }

    public function execute($isManual = false)
    {
        if (false === $this->configHelper->isInventoryEnabled() || false === $this->configHelper->isCatalogEnabled()) {
            return $this;
        }

        $cronCollection = $this->collectionFactory->create()
            ->addFieldToFilter('job_code', ['eq' => 'inventory_process'])
            ->addFieldToFilter('status', ['eq' => 'running']);
        $fh = $this->data->checkCronJobRunning('inventory');
        if (count($cronCollection) > 1) {
            $this->logger->info('Inventory jobs are more than 1');
            if ($fh === true) {
                $msg = 'Inventory synchronization is already running by the automatic Magento cron';
                $this->logger->info($msg);
                return $msg;
            }
        }

        if ($isManual === true && count($cronCollection) > 0) {
            $this->logger->info('Manual inventory run detected job collection not empty');
            if ($fh === true) {
                $msg = 'Manual start detected that inventory synchronization is already running by the automatic Magento cron';
                $this->logger->info($msg);
                return $msg;
            }
        }

        if ($isManual === true) {
            $job = $this->data->createCronJob('inventory_process');
            if (false === $job) {
                return $this;
            }
        }

        try {
            $this->inventoryModel->start();
        } catch (\Exception $e){
            $jobId = ($isManual === true)? $job->getId() : $isManual->getId();
            $this->data->cleanCronJobs($cronCollection, $jobId);
            flock($fh, LOCK_UN);
            fclose($fh);

            return 'Inventory Sync Executed';
        }

        if ($isManual === true) {
            $this->data->finishJob($job);
        }

        $jobId = ($isManual === true)? $job->getId() : $isManual->getId();
        $this->data->cleanCronJobs($cronCollection, $jobId);

        if(is_resource($fh)) {
            flock($fh, LOCK_UN);
            fclose($fh);
        }

        return 'Inventory Sync Executed';
    }
}
