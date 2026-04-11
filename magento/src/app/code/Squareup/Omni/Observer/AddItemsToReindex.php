<?php

namespace Squareup\Omni\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogInventory\Observer\ItemsForReindex;

class AddItemsToReindex implements ObserverInterface
{
    private $itemsForReindex;

    public function __construct(ItemsForReindex $itemsForReindex)
    {
        $this->itemsForReindex = $itemsForReindex;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();

        if ($order->getShippingMethod() == 'square_shipping') {
            $this->itemsForReindex->setItems([]);
        }
    }
}
