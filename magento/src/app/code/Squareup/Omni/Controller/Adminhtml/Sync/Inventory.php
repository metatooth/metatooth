<?php
/**
 * SquareUp
 *
 * Inventory Adminhtml Controller
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Model\Inventory\Import;
use Squareup\Omni\Model\Inventory\Export;
use Squareup\Omni\Model\Location\Import as LocationImport;
use Squareup\Omni\Cron\Catalog as CronCatalog;
use Squareup\Omni\Cron\Inventory as CronInventory;

/**
 * Class Inventory
 */
class Inventory extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Squareup_Omni::inventory';

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var Import
     */
    private $import;

    /**
     * @var Export
     */
    private $export;

    /**
     * @var LocationImport
     */
    private $locationImport;
    /**
     * @var CronCatalog
     */
    private $cronCatalog;
    /**
     * @var CronInventory
     */
    private $cronInventory;

    /**
     * Inventory constructor
     *
     * @param Action\Context $context
     * @param Config $config
     * @param Import $import
     * @param Export $export
     * @param LocationImport $locationImport
     * @param CronCatalog $cronCatalog
     * @param CronInventory $cronInventory
     */
    public function __construct(
        Action\Context $context,
        Config $config,
        Import $import,
        Export $export,
        LocationImport $locationImport,
        CronCatalog $cronCatalog,
        CronInventory $cronInventory
    ) {
        $this->configHelper = $config;
        $this->import = $import;
        $this->export = $export;
        $this->locationImport = $locationImport;
        $this->cronCatalog = $cronCatalog;
        parent::__construct($context);
        $this->cronInventory = $cronInventory;
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Exception
     */
    public function execute()
    {
        try {
            if (!$this->configHelper->getOAuthToken()) {
                throw new LocalizedException(
                    __('Your request did not include an `Authorization` http header with an access token')
                );
            }

            $msg = $this->cronCatalog->execute(true);
            $this->messageManager->addSuccessMessage($msg);
            $msg = $this->cronInventory->execute(true);
            $this->messageManager->addSuccessMessage($msg);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }

    /**
     * Check if user has permissions to access this controller
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
