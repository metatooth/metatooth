<?php
/**
 * SquareUp
 *
 * SaveLocation Controller
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Squareup\Omni\Logger\Logger;
use Magento\Framework\App\Cache\Manager as CacheManager;

/**
 * Class SaveLocation
 */
class SaveLocation extends Action
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * SaveLocation constructor
     *
     * @param Context $context
     * @param Validator $validator
     * @param CookieManagerInterface $cookieManager
     * @param ConfigHelper $configHelper
     * @param Logger $logger
     * @param CacheManager $cacheManager
     */
    public function __construct(
        Context $context,
        Validator $validator,
        CookieManagerInterface $cookieManager,
        ConfigHelper $configHelper,
        Logger $logger,
        CacheManager $cacheManager
    ) {
        $this->validator = $validator;
        $this->cookieManager = $cookieManager;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->cacheManager = $cacheManager;
        parent::__construct($context);
    }

    /**
     * Execute action based on request and return result
     *
     * @return mixed
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->validator->validate($this->getRequest())) {
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

        if ($this->cookieManager->getCookie('adminhtml')) {
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

        $params = $this->getRequest()->getParams();

        try {
            $this->configHelper->setLocationId($params['location']);
            $this->cacheManager->clean(['config']);
            if (!empty($this->configHelper->getOAuthToken())) {
                $this->configHelper->syncLocationInventory();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return $this->getResponse()
                ->setBody("There was an error saving the location please do it manually in admin configuration screen");
        }

        return $this->getResponse()->setBody("<h1>You can close this window</h1>");
    }
}
