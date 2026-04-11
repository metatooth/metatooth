<?php
/**
 * SquareUp
 *
 * Notify Controller
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Controller\Hooks;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Squareup\Omni\Cron\RefundsImport;
use Squareup\Omni\Cron\TransactionsImport;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Logger\Logger;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Registry;
use Squareup\Omni\Cron\Inventory;
use Squareup\Omni\Model\Transaction\Import;
use Squareup\Omni\Helper\Hook;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Notify
 */
class Notify extends Action
{
    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Registry
     */
    private $registry;

    private $inventoryCron;
    private $transaction;
    public $transactionCron;
    public $refundsCron;
    public $resultFactory;
    private $hook;

    /**
     * Callback constructor
     *
     * @param PageFactory $pageFactory
     * @param Config $config
     * @param Logger $logger
     * @param DirectoryList $directoryList
     * @param Registry $registry
     * @param Context $context
     * @param Inventory $inventoryCron
     * @param Import $transaction
     */
    public function __construct(
        TransactionsImport $transactionsImport,
        RefundsImport $refundsImport,
        PageFactory $pageFactory,
        Config $config,
        Logger $logger,
        DirectoryList $directoryList,
        Registry $registry,
        Context $context,
        Inventory $inventoryCron,
        Import $transaction,
        Hook $hook
    ) {
        $this->configHelper = $config;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->registry = $registry;
        $this->inventoryCron = $inventoryCron;
        $this->transaction = $transaction;
        $this->resultFactory = $context->getResultFactory();
        $this->transactionCron = $transactionsImport;
        $this->refundsCron = $refundsImport;
        $this->hook = $hook;
        parent::__construct($context);
        $this->runWebhook();
    }

    public function runWebhook()
    {
        if ('POST' !== $this->getRequest()->getMethod()) {
            return $this->_redirect('/');
        }

        $body = $this->getRequest()->getContent();
        $signature = $this->getRequest()->getHeader('X-Square-Signature');
        $this->logger->info($signature);
        if (true !== $this->isRequestValid($body, $signature)) {
            return $this->_redirect('/');
        }

        $notification = json_decode($body);
        $this->logger->info($body);
        switch ($notification->event_type) {
            case 'INVENTORY_UPDATED':
                if (\Squareup\Omni\Model\System\Config\Source\Options\Records::SQUARE == $this->configHelper->getSor()) {
                    $this->inventoryCron->execute();
                } else {
                    $this->logger->info('Start controller hooks');
                    $this->hook->execute($notification->location_id);
                    $this->logger->info('End controller hooks');
                }

                break;
            case 'PAYMENT_UPDATED':
                $this->transactionCron->execute(true);
                $this->refundsCron->execute(true);
//                $this->transaction->singleTransaction($notification->location_id, $notification->entity_id);
                break;
            case 'TIMECARD_UPDATED':
                $this->logger->info($body);
                break;
            default:
                $this->logger->info($body);
        }


        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(['success' => true]);
        return $resultJson;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {

    }

    private function isRequestValid($requestBody, $requestSignature)
    {
        $webhookSignatureKey = $this->configHelper->getWebhookSignature();
        $webhookUrl = $this->_url->getUrl('squareupomni/hooks/notify');

        $stringToSign = $webhookUrl . $requestBody;
        $stringSignature = base64_encode(hash_hmac('sha1', $stringToSign, $webhookSignatureKey, true));

        return (sha1($stringSignature) === sha1($requestSignature));
    }
}
