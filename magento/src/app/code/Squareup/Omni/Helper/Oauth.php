<?php
/**
 * SquareUp
 *
 * Oauth Helper
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Squareup\Omni\Logger\Logger;
use Zend_Http_Client;

/**
 * Class Oauth
 */
class Oauth extends AbstractHelper
{
    /**
     * @var array Zend http client config
     */
    public $httpClientConfig = ['adapter' => 'Zend_Http_Client_Adapter_Socket', 'timeout' => 60];

    /**
     * @var string The square endpoint
     */
    public $endpoint = 'https://connect.squareup.com';

    /**
     * @var string The application id
     */
    public $applicationId;

    /**
     * @var string The application secret
     */
    public $applicationSecret;

    /**
     * @var array Zend http client headers
     */
    public $httpClientHeaders = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    /**
     * @var \Squareup\Omni\Logger\Logger
     */
    public $logger;

    /**
     * @var \Squareup\Omni\Helper\Config
     */
    public $config;

    /**
     * OAuth constructor.
     * @param Context $context
     */
    public function __construct(
        Logger $logger,
        Config $config,
        Context $context
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->applicationId = $this->config->getApplicationId();
        $this->applicationSecret = $this->config->getApplicationSecret();
        $this->httpClientHeaders['Authorization'] = 'Client ' . $this->applicationSecret;
        parent::__construct($context);
    }

    public function getToken($authorizationCode)
    {
        $url = $this->endpoint . '/oauth2/token';
        $oauthRequestBody = [
            'client_id' => $this->applicationId,
            'client_secret' => $this->applicationSecret,
            'grant_type' => 'authorization_code',
            'code' => $authorizationCode
        ];

        $response = $this->sendRequest($url, $oauthRequestBody);
        if (false === $response) {
            return false;
        }

        $this->logger->info(json_encode($response->getBody()));
        if ($response->getStatus() != 200) {
            $this->logger->error($response->__toString());
            return false;
        }

        $responseObj = json_decode($response->getBody());
        return $responseObj;
    }

    public function renewToken($oauthToken)
    {
        $url = $this->endpoint . '/oauth2/clients/' . $this->applicationId . '/access-token/renew';
        $oauthRequestBody = [
            'access_token' => $oauthToken
        ];

        $response = $this->sendRequest($url, $oauthRequestBody);
        if (false === $response) {
            return false;
        }

        $this->logger->info(json_encode($response->getBody()));
        if ($response->getStatus() != 200) {
            $this->logger->error($response->__toString());
            return false;
        }

        $responseObj = json_decode($response->getBody());
        return $responseObj;
    }

    public function refreshToken($refreshToken)
    {
        $url = $this->endpoint . '/oauth2/token';
        $oauthRequestBody = [
            'client_id' => $this->applicationId,
            'client_secret' => $this->applicationSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];

        $response = $this->sendRequest($url, $oauthRequestBody);
        if (false === $response) {
            return false;
        }

        $this->logger->info(json_encode($response->getBody()));
        if ($response->getStatus() != 200) {
            $this->logger->error($response->__toString());
            return false;
        }

        $responseObj = json_decode($response->getBody());
        return $responseObj;
    }

    public function sendRequest($url, $body)
    {
        try {
            $client = new Zend_Http_Client($url, $this->httpClientConfig);
            $client->setMethod(Zend_Http_Client::POST);
            $client->setConfig(['timeout' => 60]);
            $client->setHeaders($this->httpClientHeaders);
            $client->setRawData(json_encode($body));
            $response = $client->request();
        } catch (\Exception $exception) {
            $this->logger->error($exception->__toString());
            return false;
        }

        return $response;
    }
}
