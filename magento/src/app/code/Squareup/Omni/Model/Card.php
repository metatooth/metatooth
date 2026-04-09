<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/9/2018
 * Time: 3:14 PM
 */

namespace Squareup\Omni\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Helper\Mapping;
use Squareup\Omni\Logger\Logger;
use Magento\Customer\Model\CustomerFactory;

class Card extends \Squareup\Omni\Model\Square
{
    /**
     * Define Card on File values
     */
    const DISALLOW_CARD_ON_FILE = 0;
    const ALLOW_CARD_ON_FILE = 1;
    const ALLOW_ONLY_CARD_ON_FILE = 2;

    /**
     * @var \SquareConnect\ApiClient
     */
    private $apiClient;
    private $alreadySavedCards = [];
    private $customerId;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;
    /**
     * @var Data
     */
    protected $helper;

    public function __construct(
        Config $config,
        Logger $logger,
        Data $helper,
        Mapping $mapping,
        Context $context,
        Registry $registry,
        CustomerFactory $customerFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $logger,
            $helper,
            $mapping,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->helper = $helper;
        $this->apiClient = $this->helper->getClientApi();
        $this->customerFactory = $customerFactory;
    }

    public function checkCcUpdates()
    {
        $customer = $this->helper->getCustomer();
        if ($customer && $customer->getId()) {
            try {
                $api = new \SquareConnect\Api\CustomersApi($this->apiClient);
                $response = $api->retrieveCustomer($customer->getSquareupCustomerId());

                if (empty($response->getErrors())) {
                    $squareCards = $response->getCustomer()->getCards();
                    if (empty($squareCards)) {
                        $customer->setId($customer->getId());
                        $customer->setSquareSavedCards(null);
                        $customer->getResource()->saveAttribute($customer, 'square_saved_cards');
                        return true;
                    }

                    $magentoCc = [];
                    foreach ($squareCards as $card) {
                        $magentoCc[$card->getId()] = [
                            'card_brand' => $card->getCardBrand(),
                            'last_4' => $card->getLast4(),
                            'exp_month' => $card->getExpMonth(),
                            'exp_year' => $card->getExpYear(),
                            'cardholder_name' => $card->getCardholderName()
                        ];
                    }

                    $squareCards = json_encode($magentoCc);
                    $customer->setId($customer->getId());
                    $customer->setSquareSavedCards($squareCards);
                    $customer->getResource()->saveAttribute($customer, 'square_saved_cards');
                    return true;
                }
            } catch (\SquareConnect\ApiException $e) {
                $this->logger->error($e->__toString());
                $this->_logger->error($e->getMessage());
                return false;
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Send customer card to square and return card_id
     * @param $customerId
     * @param $alreadySavedCards
     * @param $squareCustomerId
     * @param $request
     * @return bool|string
     */
    public function sendSaveCard($customerId, $alreadySavedCards, $squareCustomerId, $request)
    {
        try {
            $this->alreadySavedCards = $alreadySavedCards;
            $this->customerId = $customerId;
            $api = new \SquareConnect\Api\CustomersApi($this->apiClient);
            $response = $api->createCustomerCard($squareCustomerId, $request);
            if (empty($response->getErrors())) {
                $this->processCardResponse($response->getCard());
                $this->logger->info(
                    'Credit card was saved for customer ' . $customerId . ' / square_id:' . $squareCustomerId
                );
                return $response->getCard()->getId();
            }
        } catch (\SquareConnect\ApiException $e) {
            $this->logger->error($e->__toString());
            $this->_logger->error($e->getMessage());
            throw new \SquareConnect\ApiException(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            return false;
        }

        return false;
    }

    /**
     * Save card info to magento from square card response
     * @param $cardResponse
     */
    public function processCardResponse($cardResponse)
    {
        if (!empty($this->alreadySavedCards)) {
            $alreadySavedCards = json_decode($this->alreadySavedCards, true);
        }

        $alreadySavedCards[$cardResponse->getId()] = [
            'card_brand' => $cardResponse->getCardBrand(),
            'last_4' => $cardResponse->getLast4(),
            'exp_month' => $cardResponse->getExpMonth(),
            'exp_year' => $cardResponse->getExpYear(),
            'cardholder_name' => $cardResponse->getCardholderName()
        ];

        $squareCards = json_encode($alreadySavedCards);
        $customer = $this->customerFactory->create();
        $customer->setId($this->customerId);
        $customer->setSquareSavedCards($squareCards);

        try {
            $customer->getResource()->saveAttribute($customer, 'square_saved_cards');
            $this->logger->info(
                'Saving customer card in Magento customer id: '. $this->customerId
            );
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
        }
    }
}
