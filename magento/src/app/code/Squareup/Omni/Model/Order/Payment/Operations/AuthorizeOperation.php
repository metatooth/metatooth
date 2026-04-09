<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Squareup\Omni\Model\Order\Payment\Operations;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\State\CommandInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
use Squareup\Omni\Helper\Data as DataHelper;
use Squareup\Omni\Helper\Config as ConfigHelper;

class AuthorizeOperation extends \Magento\Sales\Model\Order\Payment\Operations\AuthorizeOperation
{
    /**
     * @var DataHelper
     */
    private $dataHelper;
    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * AuthorizeOperation constructor.
     * @param CommandInterface $stateCommand
     * @param BuilderInterface $transactionBuilder
     * @param ManagerInterface $transactionManager
     * @param EventManagerInterface $eventManager
     * @param DataHelper $dataHelper
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        CommandInterface $stateCommand,
        BuilderInterface $transactionBuilder,
        ManagerInterface $transactionManager,
        EventManagerInterface $eventManager,
        DataHelper $dataHelper,
        ConfigHelper $configHelper
    ) {
        parent::__construct($stateCommand, $transactionBuilder, $transactionManager, $eventManager);

        $this->dataHelper = $dataHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * Authorizes payment.
     *
     * @param OrderPaymentInterface $payment
     * @param bool $isOnline
     * @param string|float $amount
     * @return OrderPaymentInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(OrderPaymentInterface $payment, $isOnline, $amount)
    {
        // check for authorization amount to be equal to grand total
        /**
         * @var $payment Payment
         */
        $payment->setShouldCloseParentTransaction(false);
        $isSameCurrency = $payment->isSameCurrency();
        if (!$isSameCurrency || !$payment->isCaptureFinal($amount)) {
            $payment->setIsFraudDetected(true);
        }

        // update totals
        $amount = $payment->formatAmount($amount, true);
        $payment->setBaseAmountAuthorized($amount);

        // do authorization
        $order = $payment->getOrder();
        if ($isOnline) {
            // invoke authorization on gateway
            $method = $payment->getMethodInstance();
            $method->setStore($order->getStoreId());
            $method->authorize($payment, $amount);
        }

        $message = $this->stateCommand->execute($payment, $amount, $order);

        // add here
        //Remove message if is enabled: Allow only card on file and Authorize only.
        if (\Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE == $this->configHelper->getPaymentAction() &&
            \Squareup\Omni\Model\Card::ALLOW_ONLY_CARD_ON_FILE == $this->dataHelper->getCardOnFileOption() &&
            $payment->getMethodInstance()->getCode() == \Squareup\Omni\Model\Payment::CODE) {
            $message = '';
        }

        // update transactions, order state and add comments
        $transaction = $payment->addTransaction(Transaction::TYPE_AUTH);
        $message = $payment->prependMessage($message);
        $payment->addTransactionCommentsToOrder($transaction, $message);

        return $payment;
    }
}
