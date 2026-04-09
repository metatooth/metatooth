<?php

namespace Squareup\Omni\Model\Checks;

use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;

class ZeroTotal extends \Magento\Payment\Model\Checks\ZeroTotal
{
    public function isApplicable(MethodInterface $paymentMethod, Quote $quote)
    {
        if ($paymentMethod->getCode() == 'squareup_payment') {
            return true;
        }

        return !($quote->getBaseGrandTotal() < 0.0001 && $paymentMethod->getCode() != 'free');
    }
}
