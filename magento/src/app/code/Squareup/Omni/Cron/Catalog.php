<?php
/**
 * SquareUp
 *
 * Catalog Cron
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Cron;

use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Model\Catalog as SquareCatalog;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Squareup\Omni\Logger\Debugger;
use Magento\Cron\Model\ScheduleFactory;
use Squareup\Omni\Logger\Logger;

/**
 * Class Callback
 */
class Catalog extends AbstractCron
{
    /**
     * @var SquareCatalog
     */
    private $squareCatalog;

    /**
     * @var Data
     */
    private $helper;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var Debugger
     */
    private $debugger;
    /**
     * @var ScheduleFactory
     */
    private $scheduleFactory;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Catalog constructor
     *
     * @param Config $config
     * @param SquareCatalog $squareCatalog
     * @param Data $helper
     * @param CollectionFactory $collectionFactory
     * @param Debugger $debugger
     * @param ScheduleFactory $scheduleFactory
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        SquareCatalog $squareCatalog,
        Data $helper,
        CollectionFactory $collectionFactory,
        Debugger $debugger,
        ScheduleFactory $scheduleFactory,
        Logger $logger
    ) {
        $this->squareCatalog = $squareCatalog;
        $this->helper = $helper;
        parent::__construct($config);
        $this->collectionFactory = $collectionFactory;
        $this->debugger = $debugger;
        $this->scheduleFactory = $scheduleFactory;
        $this->logger = $logger;
    }

    /**
     * Execute cron
     * @param bool $isManual
     * @return bool|Catalog
     */
    public function execute($isManual = false)
    {
        if (false === $this->configHelper->isCatalogEnabled()) {
            return $this;
        }

        $cronCollection = $this->collectionFactory->create()
            ->addFieldToFilter('job_code', ['eq' => 'catalog_process'])
            ->addFieldToFilter('status', ['eq' => 'running']);

        $fh = $this->helper->checkCronJobRunning('catalog');

        if (count($cronCollection) > 1) {
            $this->logger->info('Catalog job are more than 1');
            if ($fh === true) {
                $msg = 'Catalog synchronization is already running by the automatic Magento cron';
                $this->logger->info($msg);
                return $msg;
            }
        }

        if ($isManual === true && count($cronCollection) > 0) {
            $this->logger->info('Manual catalog run detected job collection not empty');
            if ($fh === true) {
                $msg = 'Manual start detected that catalog synchronization is already running by the automatic Magento cron';
                $this->logger->info($msg);
                return $msg;
            }
        }

        if ($isManual === true) {
            $job = $this->helper->createCronJob('catalog_process');
            if (false === $job) {
                return $this;
            }
        }

        try {
            $this->squareCatalog->start();
        } catch (\Exception $e){
            $this->debugger->error($e->__toString());

            $jobId = ($isManual === true) ? $job->getId() : $isManual->getId();
            $this->helper->cleanCronJobs($cronCollection, $jobId);
            if (is_resource($fh)) {
                flock($fh, LOCK_UN);
                fclose($fh);
            }

            return 'Catalog Sync Executed';
        }

        if ($isManual === true) {
            $this->helper->finishJob($job);
        }

        $jobId = ($isManual === true) ? $job->getId() : $isManual->getId();
        $this->helper->cleanCronJobs($cronCollection, $jobId);

        if (is_resource($fh)) {
            flock($fh, LOCK_UN);
            fclose($fh);
        }

        return 'Catalog Sync Executed';
    }
}
