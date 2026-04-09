<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/15/2018
 * Time: 10:43 AM
 */

namespace Squareup\Omni\Observer\Order;

use Magento\Framework\Event\Observer;
use Squareup\Omni\Model\Inventory\Export;
use Squareup\Omni\Helper\Config;
use Magento\Framework\ObjectManagerInterface;

class Refund implements \Magento\Framework\Event\ObserverInterface
{
    private $export;

    private $configHelper;
    /**
     * @var StockItemCollectionFactory
     */
    private $stockItemCollectionFactory;
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Refund constructor.
     * @param Export $export
     * @param Config $config
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Export $export,
        Config $config,
        ObjectManagerInterface $objectManager
    ) {
        $this->export = $export;
        $this->configHelper = $config;
        $this->objectManager = $objectManager;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        $creditmemo = $observer->getCreditmemo();
        $creditmemoItems = $creditmemo->getAllItems();
        $locationId = $this->configHelper->getLocationId();

        $items = [];

        foreach ($creditmemoItems as $creditmemoItem) {
            if ($creditmemoItem->getBackToStock()) {
                $items[$creditmemoItem->getProductId()] = $creditmemoItem->getQty();
            }
        }

        $resource = $this->objectManager->create(\Magento\CatalogInventory\Model\ResourceModel\Stock\Item::class);
        $select = $resource->getConnection()
            ->select()
            ->from($resource->getMainTable())
            ->where('product_id in (?)', array_keys($items));
        $inventory = $resource->getConnection()->fetchAll($select);

        foreach ($inventory as $item) {
            $this->export->start(
                [$item['product_id']],
                $locationId,
                floatval($item['qty']) + floatval($items[$item['product_id']])
            );
        }
    }
}
