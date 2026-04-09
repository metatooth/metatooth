<?php

namespace Squareup\Omni\Cron;

use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory as ScheduleCollectionFactory;

/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 8/31/2018
 * Time: 12:33 PM
 */

class Hanging extends AbstractCron
{
    /**
     * @var Data
     */
    private $data;
    /**
     * @var ScheduleCollectionFactory
     */
    private $scheduleCollectionFactory;

    /**
     * Hanging constructor.
     * @param Config $config
     * @param Data $data
     * @param ScheduleCollectionFactory $scheduleCollectionFactory
     */
    public function __construct(
        Config $config,
        Data $data,
        ScheduleCollectionFactory $scheduleCollectionFactory
    )
    {
        parent::__construct($config);

        $this->data = $data;
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
    }

    /**
     * Execute cron
     *
     * @return mixedRan jobs by schedule.
     */
    public function execute()
    {
        // Catalog cron
        $fh = $this->data->checkCronJobRunning('catalog');
        if(is_resource($fh)){
            $hangingJob = $this->scheduleCollectionFactory->create()
                ->addFieldToFilter('job_code', ['eq' => 'catalog_process'])
                ->addFieldToFilter('status', ['eq' => 'running'])
                ->getFirstItem();

            if($hangingJob->getId()){
                flock($fh, LOCK_UN);
                $hangingJob->setStatus('failed')
                    ->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
                    ->setMessage('Marked as hanging')
                    ->save();
            }
            fclose($fh);
        }

        // Inventory cron
        $fh = $this->data->checkCronJobRunning('inventory_process');
        if(is_resource($fh)){
            $hangingJob = $this->scheduleCollectionFactory->create()
                ->addFieldToFilter('job_code', ['eq' => 'inventory_process'])
                ->addFieldToFilter('status', ['eq' => 'running'])
                ->getFirstItem();

            if($hangingJob->getId()){
                flock($fh, LOCK_UN);
                $hangingJob->setStatus('failed')
                    ->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
                    ->setMessage('Marked as hanging')
                    ->save();
            }
            fclose($fh);
        }
    }
}
