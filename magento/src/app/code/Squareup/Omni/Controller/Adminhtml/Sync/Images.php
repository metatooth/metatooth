<?php
/**
 * SquareUp
 *
 * Images Adminhtml Controller
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
use Squareup\Omni\Cron\Images as CronImages;
use Squareup\Omni\Helper\Config;

/**
 * Class Images
 */
class Images extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Squareup_Omni::images';

    /**
     * @var CronImages
     */
    private $cronImages;

    private $configHelper;

    /**
     * Images constructor
     *
     * @param Action\Context $context
     * @param CronImages $cronImages
     * @param Config $configHelper
     */
    public function __construct(
        Action\Context $context,
        CronImages $cronImages,
        Config $configHelper
    ) {
        $this->cronImages = $cronImages;
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
            $this->cronImages->execute(true);
            $this->messageManager->addSuccessMessage('Images Sync Executed');
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
