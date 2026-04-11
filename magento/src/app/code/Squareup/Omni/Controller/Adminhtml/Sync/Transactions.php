<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/14/2018
 * Time: 4:54 PM
 */

namespace Squareup\Omni\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Squareup\Omni\Cron\TransactionsImport;
use Squareup\Omni\Cron\RefundsImport;
use Squareup\Omni\Helper\Config;

/**
 * Class Transactions
 * @package Squareup\Omni\Controller\Adminhtml\Sync
 */
class Transactions extends Action
{
    /**
     * @var TransactionsImport
     */
    private $transactionsImport;
    /**
     * @var RefundsImport
     */
    private $refundsImport;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * Transactions constructor.
     * @param Action\Context $context
     * @param TransactionsImport $transactionsImport
     * @param RefundsImport $refundsImport
     */
    public function __construct(
        Action\Context $context,
        TransactionsImport $transactionsImport,
        RefundsImport $refundsImport,
        Config $configHelper
    ) {
        parent::__construct($context);

        $this->transactionsImport = $transactionsImport;
        $this->refundsImport = $refundsImport;
        $this->configHelper = $configHelper;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        try {
            if (!$this->configHelper->getOAuthToken()) {
                throw new LocalizedException(
                    __('Your request did not include an `Authorization` http header with an access token')
                );
            }
            $this->transactionsImport->execute(true);
            $this->refundsImport->execute(true);

            $this->messageManager->addSuccessMessage(__("Transactions and Refunds Sync Executed"));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}
