<?php
/**
 * SquareUp
 *
 * Inventory ResourceModel
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Squareup\Omni\Model\InventoryFactory;

/**
 * Class Inventory
 */
class Inventory extends AbstractDb
{
    /**
     * @var InventoryFactory
     */
    private $inventoryFactory;

    /**
     * Inventory constructor
     *
     * @param InventoryFactory $inventoryFactory
     * @param Context $context
     * @param null $connectionName
     */
    public function __construct(
        InventoryFactory $inventoryFactory,
        Context $context,
        $connectionName = null
    ) {
        $this->inventoryFactory = $inventoryFactory;
        parent::__construct($context, $connectionName);
    }

    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init(
            $this->_resources->getTableName('squareup_omni_inventory'),
            'id'
        );
    }

    /**
     * Load inventory by product id and location id
     *
     * @param $productId
     * @param $locationId
     *
     * @return mixed
     */
    public function loadByProductIdAndLocationId($productId, $locationId)
    {
        $inventory = $this->inventoryFactory->create();
        $connection = $this->_resources->getConnection();
        $select = $connection
            ->select()
            ->from($this->_mainTable)
            ->where('product_id = ?', (int)$productId)
            ->where('location_id = ?', $locationId);

        $data = $connection->fetchRow($select);

        if (!$data) {
            return null;
        }

        $inventory->setData($data);

        return $inventory;
    }

    /**
     * Empty inventory table
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function emptyInventory()
    {
        $connection = $this->_resources->getConnection();
        return $connection->truncateTable($this->_resources->getTableName('squareup_omni_inventory'));
    }
}
