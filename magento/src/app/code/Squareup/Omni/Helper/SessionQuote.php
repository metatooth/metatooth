<?php
/**
 * SquareUp
 *
 * SessionQuote Helper
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\App\Helper\Context;

/**
 * Class SessionQuote
 */
class SessionQuote extends AbstractHelper
{
    private $sessionQuote;

    public function __construct(Context $context, Quote $sessionQuote)
    {
        parent::__construct($context);

        $this->sessionQuote = $sessionQuote;
    }

    public function getData()
    {
        $quote = $this->sessionQuote->getQuote();
        $billingAddress = $quote->getBillingAddress();

        return \Zend_Json::encode([
            'familyName' => $billingAddress->getFirstname(),
            'givenName' => $billingAddress->getLastname(),
            'email' => $billingAddress->getEmail(),
            'country' => $billingAddress->getCountryId(),
            'addressLines' => $billingAddress->getStreet(),
            'postalCode' => $billingAddress->getPostcode(),
            'phone' => $billingAddress->getTelephone(),
            'amount' => $quote->getGrandTotal()
        ]);
    }
}
