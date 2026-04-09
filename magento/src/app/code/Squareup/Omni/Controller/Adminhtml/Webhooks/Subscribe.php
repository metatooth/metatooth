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

namespace Squareup\Omni\Controller\Adminhtml\Webhooks;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;

use Squareup\Omni\Helper\Data as OmniDataHelper;
use Squareup\Omni\Logger\Logger as OmniLogHelper;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Squareup\Omni\Model\ResourceModel\Location\CollectionFactory;

class Subscribe extends Action
{
    /**
     * Log Helper
     *
     * @var \Squareup\Omni\Logger\Logger
     */
    private $logHelper;
    /**
     * @var \Squareup\Omni\Helper\Config
     */
    private $configHelper;
    /**
     * @var CollectionFactory
     */
    private $locationCollectionFactory;
    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Squareup_Omni::webhooks';

    public function __construct(
        Action\Context $context,
        OmniDataHelper $omniDataHelper,
        OmniLogHelper $omniLogHelper,
        ConfigHelper $configHelper,
        JsonFactory $jsonResultFactory,
        CollectionFactory $locationCollectionFactory
    ) {
        parent::__construct($context);
        $this->logHelper = $omniLogHelper;
        $this->configHelper = $configHelper;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->locationCollectionFactory = $locationCollectionFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     */
    public function execute()
    {
        $token = $this->configHelper->getOAuthToken();
        if (null === $token) {
            $this->logHelper->error('Token not found on webhooks subscribe');
            return $this;
        }

        $locationIds = [];
        $collection = $this->locationCollectionFactory->create()
            ->addFieldToFilter('status', ['eq' => 1]);
        foreach ($collection as $item) {
            $locationIds[] = $item->getSquareId();
        }

        $errors = [];
        foreach ($locationIds as $locationId) {
            $url = 'https://connect.squareup.com/v1/' . $locationId . '/webhooks';

            $config = [
                'adapter'   => 'Zend_Http_Client_Adapter_Socket',
            ];
            $oauthRequestHeaders = [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ];
            $oauthRequestBody = ["PAYMENT_UPDATED", "INVENTORY_UPDATED"];

            try {
                $client = new \Zend_Http_Client($url, $config);
                $client->setMethod(\Zend_Http_Client::PUT);
                $client->setConfig(['timeout' => 60]);
                $client->setHeaders($oauthRequestHeaders);
                $client->setRawData(json_encode($oauthRequestBody));
                $response = $client->request();
            } catch (\Exception $e) {
                $this->logHelper->error($e->__toString());
                $errors[] = false;
            }

            $responseObject = json_decode($response->getBody());
            $this->logHelper->info($response->getBody());
            if (isset($responseObject->type)) {
                $errors[] = false;
            }
        }

        $result = $this->jsonResultFactory->create();
        if (in_array(false, $errors)) {
            $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
            $result->setData(['error' => true]);
        } else {
            $result->setData(['error' => false]);
        }

        return $result;
    }
}
