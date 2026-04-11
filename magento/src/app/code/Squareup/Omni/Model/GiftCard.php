<?php
/**
 * SquareUp
 *
 * GiftCard Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\HTTP\Client\Curl;
use Squareup\Omni\Helper\Config;

/**
 * Class GiftCard
 */
class GiftCard extends AbstractModel
{
    private $curlClient;

    private $config;

    public function __construct(
        Context $context,
        Registry $registry,
        Curl $curlClient,
        Config $config,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ){
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->curlClient = $curlClient;
        $this->config = $config;
    }

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(\Squareup\Omni\Model\ResourceModel\GiftCard::class);
    }

    public function getBalance($giftCardNonce)
    {
        $balance = 0;
        $headers = [
            'Authorization' => 'Bearer ' . $this->config->getOAuthToken(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $params = \Zend_Json::encode(['nonce' => $giftCardNonce]);
        $this->curlClient->setHeaders($headers);
        $this->curlClient->post('https://connect.squareup.com/v2/giftcards/nonce', $params);
        $response = \Zend_Json::decode($this->curlClient->getBody());

        if (isset($response['gift_card'])) {
            $balance = $response['gift_card']['balance']['amount'];
        }

        return (float)$balance / 100;
    }
}
