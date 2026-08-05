<?php
declare(strict_types=1);

namespace Metatooth\SquareCheckout\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class Payment extends AbstractMethod
{
    public const CODE = 'squarecheckout';

    protected $_code = self::CODE;
    protected $_canOrder = true;
    protected $_canCapture = false;
    protected $_isGateway = false;
    protected $_isOffline = false;
}
