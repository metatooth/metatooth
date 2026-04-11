<?php
/**
 * SquareUp
 *
 * Callback Controller
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Hook;
use Squareup\Omni\Helper\Oauth;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\Location\Import;
use Zend_Http_Client;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Registry;

/**
 * Class Callback
 */
class Callback extends Action
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Import
     */
    private $locationImport;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Registry
     */
    private $registry;

    private $hook;

    public $oAuthHelper;

    /**
     * Callback constructor
     *
     * @param PageFactory $pageFactory
     * @param Config $config
     * @param Logger $logger
     * @param Import $locationImport
     * @param DirectoryList $directoryList
     * @param Registry $registry
     * @param Context $context
     */
    public function __construct(
        PageFactory $pageFactory,
        Config $config,
        Logger $logger,
        Import $locationImport,
        DirectoryList $directoryList,
        Registry $registry,
        Context $context,
        Hook $hook,
        Oauth $oAuthHelper
    ) {
        $this->pageFactory = $pageFactory;
        $this->configHelper = $config;
        $this->logger = $logger;
        $this->locationImport = $locationImport;
        $this->directoryList = $directoryList;
        $this->registry = $registry;
        $this->hook = $hook;
        $this->oAuthHelper = $oAuthHelper;
        return parent::__construct($context);
    }

    /**
     * Execute action based on request and return result
     *
     * @return mixed
     * @throws \Exception
     */
    public function execute()
    {
        $authorizationCode = $this->getRequest()->getParam('code');
        $state = $this->getRequest()->getParam('state');
        $fh = new \SplFileObject($this->directoryList->getPath('var') . '/onlytoken.txt', 'r');
        $storedState = $fh->fgets();
        $fh = null;

        if ($storedState !== $state) {
            return $this->getResponse()
                ->setBody('There was an error with state, please try again!');
        }

        if (null === $authorizationCode) {
            return $this->getResponse()
                ->setBody('There was an error please check Application Key and Application Secret and try again!');
        }

        $responseObj = $this->oAuthHelper->getToken($authorizationCode);
        if (false === $responseObj) {
            return $this->getResponse()
                ->setBody(
                    'There was an error in retrieving the access token, please check Application Key
                    and Application Secret and try again!'
                );
        }

        $this->configHelper->setConfig('squareup_omni/oauth_settings/oauth_token', $responseObj->access_token);
        $this->configHelper->setConfig(
            'squareup_omni/oauth_settings/oauth_expire',
            strtotime($responseObj->expires_at)
        );

        if (property_exists($responseObj, 'refresh_token')) {
            $this->configHelper->saveOauthRefresh($responseObj->refresh_token);
        }

        $this->registry->register('square_oauth_token', $responseObj->access_token);

        $this->locationImport->updateLocations();

        return $this->pageFactory->create();
    }
}
