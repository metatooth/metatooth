<?php
/**
 * SquareUp
 *
 * Catalog Adminhtml Controller
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
use Squareup\Omni\Cron\Catalog as CronCatalog;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Model\Location\Import as LocationImport;
use Squareup\Omni\Helper\Config;

/**
 * Class Catalog
 */
class Catalog extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Squareup_Omni::catalog';

    /**
     * @var CronCatalog
     */
    private $cronCatalog;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var LocationImport
     */
    private $locationImport;

    private $configHelper;

    /**
     * Catalog constructor
     *
     * @param Action\Context $context
     * @param CronCatalog $cronCatalog
     * @param Data $helper
     * @param LocationImport $locationImport
     */
    public function __construct(
        Action\Context $context,
        CronCatalog $cronCatalog,
        Data $helper,
        LocationImport $locationImport,
        Config $configHelper
    ) {
        $this->cronCatalog = $cronCatalog;
        $this->helper = $helper;
        $this->locationImport = $locationImport;
        $this->configHelper = $configHelper;
        parent::__construct($context);
    }

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

            $this->locationImport->updateLocations();

            $msg = $this->cronCatalog->execute(true);
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
