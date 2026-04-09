<?php
/**
 * SquareUp
 *
 * RefundsImport Cron
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Cron;

use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Model\Refunds\ImportFactory;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Squareup\Omni\Model\ResourceModel\Location\Collection;

/**
 * Class RefundsImport
 */
class RefundsImport extends AbstractCron
{
    /**
     * @var ImportFactory
     */
    private $importFactory;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public $locationCollection;

    public $registry;
    /**
     * RefundsImport constructor.
     * @param Config $config
     * @param ImportFactory $importFactory
     * @param CollectionFactory $collectionFactory
     * @param Collection $locationCollection
     */
    public function __construct(
        Config $config,
        ImportFactory $importFactory,
        CollectionFactory $collectionFactory,
        Collection $locationCollection,
        Registry $registry
    ) {
        parent::__construct($config);

        $this->importFactory = $importFactory;
        $this->collectionFactory = $collectionFactory;
        $this->locationCollection = $locationCollection;
        $this->registry = $registry;
    }

    public function execute($isManual = false)
    {
        if (!$this->configHelper->getAllowImportTrans()) {
            return false;
        }

        $cronCollection = $this->collectionFactory->create()
            ->addFieldToFilter('job_code', ['eq' => 'square_refunds_import'])
            ->addFieldToFilter('status', ['eq' => 'running']);

        if ($isManual || count($cronCollection) === 1) {
            $locations = $this->locationCollection->addFieldToFilter('status', ['eq' => 1]);
            $beginTime = $this->configHelper->getRefundsBeginTime();
            foreach ($locations as $location) {
                $this->importFactory->create()->importRefunds($location->getSquareId(), $beginTime);
            }

            $beginTimeToSave = date("c", time());
            $this->configHelper->setRefundsBeginTime($beginTimeToSave);
            $this->registry->register('squareup_omni_refunds_begin_time', $beginTimeToSave);
        }

        return true;
    }
}
