<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 7/2/2018
 * Time: 5:16 PM
 */

namespace Squareup\Omni\Controller\Adminhtml\Oauth;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Zend_Http_Client;
use Squareup\Omni\Helper\Config as ConfigHelper;

class RevokeToken extends Action
{
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * RevokeToken constructor.
     * @param Action\Context $context
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Action\Context $context,
        ConfigHelper $configHelper
    ) {
        parent::__construct($context);

        $this->configHelper = $configHelper;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            if (!$this->getRequest()->getPost() || !$this->getRequest()->isAjax()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Forbidden'));
            }

            $config = [
                'adapter' => 'Zend_Http_Client_Adapter_Socket',
            ];

            $isSandbox = $this->configHelper->getApplicationMode() === 'sandbox' ? true : false;
            $oauthRequestHeaders =  [
                'Authorization' => 'Client ' . $this->configHelper->getApplicationSecret($isSandbox),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $oauthRequestBody = [
                'client_id' => $this->configHelper->getApplicationId($isSandbox),
                'access_token' => $this->configHelper->getOAuthToken()
            ];

            $client = new Zend_Http_Client('https://connect.squareup.com/oauth2/revoke', $config);
            $client->setMethod(Zend_Http_Client::POST);
            $client->setConfig(['timeout' => 60]);
            $client->setHeaders($oauthRequestHeaders);
            $client->setRawData(json_encode($oauthRequestBody));
            $response = $client->request();

            $responseObj = json_decode($response->getBody(), true);

            if (isset($responseObj['success']) && $responseObj['success']) {
                $this->configHelper->setConfig('squareup_omni/oauth_settings/oauth_token', null);
            } else {
                $responseMessage = !empty($responseObj['message']) ? $responseObj['message'] : 'Failed.';
                throw new \Magento\Framework\Exception\LocalizedException(__($responseMessage));
            }

            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
