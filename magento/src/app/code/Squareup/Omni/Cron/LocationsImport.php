<?php
/**
 * SquareUp
 *
 * LocationsImport Cron
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Cron;

use Squareup\Omni\Model\Location\Import;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;

/**
 * Class LocationsImport
 */
class LocationsImport
{
    /**
     * @var Import
     */
    private $locationImport;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * LocationsImport constructor
     *
     * @param Import $import
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Import $import,
        CollectionFactory $collectionFactory
    ) {
        ini_set('memory_limit', -1);
        $this->locationImport = $import;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Import \ Update locations
     *
     * @return bool
     * @throws \Exception
     */
    public function execute()
    {
        $cronCollection = $this->collectionFactory->create()
            ->addFieldToFilter('job_code', ['eq' => 'location_import'])
            ->addFieldToFilter('status', ['eq' => 'running']);

        if (count($cronCollection) === 1) {
            $this->locationImport->updateLocations();
        }

        return true;
    }
}
