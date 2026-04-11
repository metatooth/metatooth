<?php
/**
 * SquareUp
 *
 * TransactionsImport Cron
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Cron;

use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Model\Transaction\ImportFactory;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Squareup\Omni\Model\ResourceModel\Location\Collection;

/**
 * Class TransactionsImport
 */
class TransactionsImport extends AbstractCron
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
     * TransactionsImport constructor.
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
            ->addFieldToFilter('job_code', ['eq' => 'square_transactions_import'])
            ->addFieldToFilter('status', ['eq' => 'running']);

        if ($isManual || count($cronCollection) === 1) {
            $locations = $this->locationCollection->addFieldToFilter('status', ['eq' => 1]);
            $beginTime = $this->configHelper->getTransactionsBeginTime();
            foreach ($locations as $location) {
                $this->importFactory->create()->importTransactions($location->getSquareId(), $beginTime);
            }

            $beginTimeToSave = date("c", time());
            $this->configHelper->setTransactionsBeginTime($beginTimeToSave);
            $this->registry->register('squareup_omni_transactions_begin_time', $beginTimeToSave);
        }

        return true;
    }
}
