<?php
/**
 * SquareUp
 *
 * Images Cron
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Cron;

use Squareup\Omni\Helper\Config;
use Squareup\Omni\Model\Catalog\Images as ImagesModel;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Squareup\Omni\Logger\Debugger;

/**
 * Class Images
 */
class Images extends AbstractCron
{
    private $imagesModel;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var Debugger
     */
    private $debugger;

    /**
     * Images constructor.
     * @param Config $config
     * @param ImagesModel $imagesModel
     * @param CollectionFactory $collectionFactory
     * @param Debugger $debugger
     */
    public function __construct(
        Config $config,
        ImagesModel $imagesModel,
        CollectionFactory $collectionFactory,
        Debugger $debugger
    ) {
        $this->imagesModel = $imagesModel;
        parent::__construct($config);
        $this->collectionFactory = $collectionFactory;
        $this->debugger = $debugger;
    }

    /**
     * Execute cron
     * @param bool $isManual
     * @return bool|Images
     */
    public function execute($isManual = false)
    {
        if (false === $this->configHelper->isImagesEnabled() || false === $this->configHelper->isCatalogEnabled()) {
            return $this;
        }

        $cronCollection = $this->collectionFactory->create()
            ->addFieldToFilter('job_code', ['eq' => 'images_process'])
            ->addFieldToFilter('status', ['eq' => 'running']);

        if ($isManual || count($cronCollection) === 1) {
            $this->imagesModel->start();
        }

        return true;
    }
}
