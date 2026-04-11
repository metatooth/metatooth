<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/8/2018
 * Time: 5:22 PM
 */

namespace Squareup\Omni\Model\Transaction;

use Magento\Sales\Model\Order\Creditmemo;
use Squareup\Omni\Model\CardFactory;
use Squareup\Omni\Logger\Logger as SquareupLogger;
use Squareup\Omni\Helper\Config as ConfigHelper;
use Squareup\Omni\Helper\Data as DataHelper;
use Squareup\Omni\Model\Order\Export as ExportOrder;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Exception\LocalizedException;
use Squareup\Omni\Model\System\Config\Source\Options\Mode;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'squareup_transaction_payment';

    protected $_code = 'squareup_transaction_payment';

    protected $_canUseCheckout = false;

    protected $_canUseInternal = false;
}
