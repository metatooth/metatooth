<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/9/2018
 * Time: 4:54 PM
 */

namespace Squareup\Omni\Model;

use Squareup\Omni\Helper\Data as SquareupDataHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Magento\Store\Model\StoreManagerInterface;
use Squareup\Omni\Model\ResourceModel\GiftCard\CollectionFactory as GiftCardCollection;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    const CODE = 'squareup';

    /**
     * @var SquareupDataHelper
     */
    private $squareupDataHelper;
    /**
     * @var CustomerSession
     */
    private $customerSession;
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    private $storeManager;

    private $giftCardCollection;

    private $cartRepository;

    private $checkoutSession;

    /**
     * ConfigProvider constructor.
     * @param SquareupDataHelper $squareupDataHelper
     * @param CustomerSession $customerSession
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        SquareupDataHelper $squareupDataHelper,
        CustomerSession $customerSession,
        ConfigHelper $configHelper,
        StoreManagerInterface $storeManager,
        GiftCardCollection $giftCardCollection,
        CartRepositoryInterface $cartRepository,
        CheckoutSession $checkoutSession
    ) {
        $this->squareupDataHelper = $squareupDataHelper;
        $this->customerSession = $customerSession;
        $this->configHelper = $configHelper;
        $this->storeManager = $storeManager;
        $this->giftCardCollection = $giftCardCollection;
        $this->cartRepository = $cartRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConfig()
    {
        $customerCards = $this->squareupDataHelper->getCustomerCards();

        $isSandbox = $this->configHelper->isSandbox();

        $websiteId = $this->storeManager->getWebsite()->getId();
        $storeId = $this->storeManager->getStore()->getId();

        if ($locationId = $this->configHelper->getLocationId('store', $storeId)) {}
        elseif ($locationId = $this->configHelper->getLocationId('website', $websiteId)) {}
        else {
            $locationId = $this->configHelper->getLocationId();
        }

        $giftCards = $this->getGiftCards();

        return [
            'payment' => [
                self::CODE => [
                    'squareupApplicationId' => $this->configHelper->getApplicationId($isSandbox),
                    'squareupLocationId' => $locationId,
                    'getHaveSavedCards' => $this->squareupDataHelper->haveSavedCards(),
                    'getIsSaveOnFileEnabled' => $this->squareupDataHelper->isSaveOnFileEnabled(),
                    'customerCards' => $customerCards,
                    'getCustomerCards' => !empty($customerCards) && is_array($customerCards) ? true : false,
                    'getCanSaveCards' => $this->squareupDataHelper->canSaveCards(),
                    'cardInputTitles' => $this->getCardInputTitles(),
                    'displaySaveCcCheckbox' => $this->squareupDataHelper->displaySaveCcCheckbox(),
                    'onlyCardOnFileEnabled' => ($this->squareupDataHelper->canSaveCards() &&
                        $this->squareupDataHelper->onlyCardOnFileEnabled()) ? true : false,
                    'isGiftCardEnabled' => $this->configHelper->isGiftCardEnabled(),
                    'quoteGiftCards' => $giftCards ? \Zend_Json::encode($giftCards) : '',
                    'squareupCurrencyCode' => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                    'squareupMerchantName' => $this->storeManager->getStore()->getFrontendName()
                ]
            ]
        ];
    }

    private function getGiftCards()
    {
        $quoteId = $this->checkoutSession->getQuoteId();
        $giftCards = [];
        $collection = $this->giftCardCollection->create()
            ->addFieldToFilter('quote_id', ['eq' => $quoteId]);

        if ($collection->count()) {
            $quote = $this->cartRepository->get($quoteId);
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $this->cartRepository->save($quote->collectTotals());

            $collection = $this->giftCardCollection->create()
                ->addFieldToFilter('quote_id', ['eq' => $quoteId]);
        }

        foreach ($collection as $giftCard) {
            $giftCards[] = [
                'card_code' => $giftCard->getCardCode(),
                'amount' => $giftCard->getAmount()
            ];
        }

        return $giftCards;
    }

    /**
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCardInputTitles()
    {

        $customer = $this->customerSession->getCustomer();
        $savedCards = json_decode($customer->getSquareSavedCards(), true);

        if (!empty($savedCards) && is_array($savedCards)) {
            foreach ($savedCards as $key => $card) {
                $savedCards[$key] = $card['cardholder_name'] . ' | ' . $card['card_brand'] .
                    ' | ' . $card['exp_month'] . '/' . $card['exp_year'] . ' | **** ' . $card['last_4'];
            }

            return $savedCards;
        }

        return [];
    }
}
