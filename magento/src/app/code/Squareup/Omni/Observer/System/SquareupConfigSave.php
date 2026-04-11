<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/15/2018
 * Time: 10:57 AM
 */

namespace Squareup\Omni\Observer\System;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Registry;
use Magento\CatalogInventory\Model\Stock\ItemFactory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResource;
use Squareup\Omni\Logger\Logger;

class SquareupConfigSave implements ObserverInterface
{
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var \Squareup\Omni\Helper\Config
     */
    private $configHelper;
    /**
     * @var \Squareup\Omni\Model\InventoryFactory
     */
    private $inventoryFactory;
    /**
     * @var \Squareup\Omni\Model\LocationFactory
     */
    private $locationFactory;
    /**
     * @var \Squareup\Omni\Helper\Data
     */
    private $dataHelper;
    /**
     * @var \Squareup\Omni\Model\RefundsFactory
     */
    private $refundsFactory;
    /**
     * @var \Squareup\Omni\Model\TransactionsFactory
     */
    private $transactionsFactory;
    /**
     * @var \Squareup\Omni\Model\ResourceModel\Product
     */
    private $productResource;
    /**
     * @var \Squareup\Omni\Model\Location\Import
     */
    private $locationImport;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var WriterInterface
     */
    private $writer;
    /**
     * @var ItemResource
     */
    private $itemResource;
    /**
     * @var ItemFactory
     */
    private $itemFactory;
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;
    /**
     * @var EventManager
     */
    private $eventManager;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * SquareupConfigSave constructor
     *
     * @param Registry $registry
     * @param \Squareup\Omni\Helper\Config $configHelper
     * @param \Squareup\Omni\Helper\Data $dataHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writer
     * @param ItemResource $itemResource
     * @param \Squareup\Omni\Model\InventoryFactory $inventoryFactory
     * @param \Squareup\Omni\Model\TransactionsFactory $transactionsFactory
     * @param \Squareup\Omni\Model\RefundsFactory $refundsFactory
     * @param \Squareup\Omni\Model\ResourceModel\Product $productResource
     * @param \Squareup\Omni\Model\LocationFactory $locationFactory
     * @param \Squareup\Omni\Model\Location\Import $locationImport
     * @param ItemFactory $itemFactory
     * @param ReinitableConfigInterface $reinitableConfig
     * @param EventManager $eventManager
     */
    public function __construct(
        Registry $registry,
        \Squareup\Omni\Helper\Config $configHelper,
        \Squareup\Omni\Helper\Data $dataHelper,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writer,
        ItemResource $itemResource,
        \Squareup\Omni\Model\InventoryFactory $inventoryFactory,
        \Squareup\Omni\Model\TransactionsFactory $transactionsFactory,
        \Squareup\Omni\Model\RefundsFactory $refundsFactory,
        \Squareup\Omni\Model\ResourceModel\Product $productResource,
        \Squareup\Omni\Model\LocationFactory $locationFactory,
        \Squareup\Omni\Model\Location\Import $locationImport,
        ItemFactory $itemFactory,
        ReinitableConfigInterface $reinitableConfig,
        EventManager $eventManager,
        Logger $logger
    ) {
        $this->registry = $registry;
        $this->configHelper = $configHelper;
        $this->scopeConfig = $scopeConfig;
        $this->inventoryFactory = $inventoryFactory;
        $this->locationFactory = $locationFactory;
        $this->dataHelper = $dataHelper;
        $this->transactionsFactory = $transactionsFactory;
        $this->refundsFactory = $refundsFactory;
        $this->productResource = $productResource;
        $this->locationImport = $locationImport;
        $this->writer = $writer;
        $this->itemResource = $itemResource;
        $this->itemFactory = $itemFactory;
        $this->reinitableConfig = $reinitableConfig;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return SquareupConfigSave
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $before = $this->registry->registry('before_app_mode');
        $after = $this->configHelper->getApplicationMode();
        $before_square_application_id = $this->registry->registry('square_application_id');
        $after_square_application_id = $this->configHelper->getApplicationId();
        $before_square_application_secret = $this->registry->registry('square_application_secret');
        $after_square_application_secret = $this->configHelper->getApplicationSecret();
        if (($before != $after) ||
            ($before_square_application_id != $after_square_application_id) ||
            ($before_square_application_secret != $after_square_application_secret)) {
            if (($before == 'prod') && ($before_square_application_id == $after_square_application_id)) {
                $this->configHelper->setConfig(
                    'squareup_omni/general/location_id_old',
                    $this->registry->registry('before_location_id')
                );
            }
            if(false === $this->configHelper->isPaymentOnly()){
                $this->logger->info("Omni integration is enabled");
                $this->inventoryFactory->create()->getResource()->emptyInventory();
                $this->locationFactory->create()->getResource()->emptyLocations();

                /* Delete all square flag from customers */
                $this->dataHelper->resetSquareCustomerFlag();
                /* Delete all square transactions from magento */
                $this->transactionsFactory->create()->getResource()->emptyTransactions();
                /* Delete all square transactions from magento */
                $this->refundsFactory->create()->getResource()->emptyRefunds();

                $this->productResource->resetProducts();

                $this->configHelper->saveRanAt();
                $this->configHelper->saveImagesRanAt();
                $this->configHelper->setTransactionsBeginTime();
                $this->configHelper->setRefundsBeginTime();
            } else {
                $this->logger->info("Payment only is enabled");
            }

            if (($before_square_application_id != $after_square_application_id) ||
                ($before_square_application_secret != $after_square_application_secret)) {
                $this->configHelper->saveOauthToken();
                $this->configHelper->saveOauthExpire();
            } else {
                if (!empty($this->configHelper->getOAuthToken())) {
                    $this->locationImport->updateLocations();
                    $this->writer->save(
                        'squareup_omni/general/location_id',
                        $this->configHelper->getOldLocationId()
                    );
                    $this->writer->save('squareup_omni/general/location_id_old', '');
                }
            }

            $this->reinitableConfig->reinit();
            $this->registry->register('squareup_omni_clear_fpcache', true);
        }

        if (!empty($this->configHelper->getOAuthToken())) {
            $this->syncLocationInventory();
        }

        return $this;
    }

    /**
     * Synchronize inventory on location change
     * @throws \Exception
     */
    private function syncLocationInventory()
    {
        if ($this->registry->registry('before_location_id') != ($locationId = $this->configHelper->getLocationId())) {
            $inventory = $this->inventoryFactory->create()
                ->getCollection()
                ->addFieldToFilter('location_id', ['eq' => $locationId]);

            $inventoryArr = [];

            foreach ($inventory as $item) {
                $inventoryArr[$item->getProductId()] = $item->getQuantity();
            }

            $select = $this->itemResource->getConnection()->select()->from($this->itemResource->getMainTable());
            $stockItems = $this->itemResource->getConnection()->fetchAll($select);

            foreach ($stockItems as $stockItem) {
                $stockItem = $this->itemFactory->create()->load($stockItem['item_id']);
                // prevents a notice error
                $quantity = !empty($inventoryArr[$stockItem->getProductId()]) ?
                    $inventoryArr[$stockItem->getProductId()] : 0;
                $stockItem->getManageStock();
                $stockItem->setQty($quantity);
                $stockItem->setIsInStock((int)($quantity > 0));
                $stockItem->save();
            }
        }
    }
}
