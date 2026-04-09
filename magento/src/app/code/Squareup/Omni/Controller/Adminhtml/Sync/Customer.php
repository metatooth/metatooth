<?php
/**
 * SquareUp
 *
 * Customer Adminhtml Controller
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
use Squareup\Omni\Cron\CustomerImport as CustomerImportCron;
use Squareup\Omni\Cron\CustomerExport as CustomerExportCron;
use Squareup\Omni\Logger\Logger;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Squareup\Omni\Helper\Config;

/**
 * Class Customer
 */
class Customer extends Action
{
    /**
     * @var CustomerImportCron
     */
    private $customerImportCron;
    /**
     * @var CustomerExportCron
     */
    private $customerExportCron;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var DateTime
     */
    private $dateTime;

    private $configHelper;

    /**
     * Customer constructor.
     * @param Action\Context $context
     * @param CustomerImportCron $customerImportCron
     * @param CustomerExportCron $customerExportCron
     * @param Logger $logger
     * @param DateTime $dateTime
     */
    public function __construct(
        Action\Context $context,
        CustomerImportCron $customerImportCron,
        CustomerExportCron $customerExportCron,
        Logger $logger,
        DateTime $dateTime,
        Config $configHelper
    ) {
        parent::__construct($context);

        $this->customerImportCron = $customerImportCron;
        $this->customerExportCron = $customerExportCron;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->configHelper = $configHelper;
    }

    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Squareup_Omni::customer';

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            if (!$this->configHelper->getOAuthToken()) {
                throw new LocalizedException(
                    __('Your request did not include an `Authorization` http header with an access token')
                );
            }

            $this->logger->info('Manual Customer Sync started' . $this->dateTime->timestamp());
            $this->logger->info('Manual Customer Import started' . $this->dateTime->timestamp());

            $this->customerImportCron->execute(true);

            $this->logger->info('Manual Customer Import ended' . $this->dateTime->timestamp());
            $this->logger->info('Manual Customer Export started' . $this->dateTime->timestamp());

            $this->customerExportCron->execute(true);

            $this->logger->info('Manual Customer Export ended' . $this->dateTime->timestamp());

            $this->messageManager->addSuccessMessage(__('Customer Sync Executed'));
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
