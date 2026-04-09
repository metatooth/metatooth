<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/15/2018
 * Time: 11:35 AM
 */

namespace Squareup\Omni\Plugin;

use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;

class JoinLocationToProductCollectionPlugin
{
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * JoinLocationToProductCollectionPlugin constructor.
     * @param Registry $registry
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Registry $registry,
        ResourceConnection $resourceConnection
    ) {
        $this->registry = $registry;
        $this->resourceConnection = $resourceConnection;
    }

    public function beforeLoad(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection,
        $printQuery = false,
        $logQuery = false
    ) {
        if (!$this->registry->registry('join_squareup_omni_inventory')) {
            $collection->getSelect()->joinLeft(
                ['square_inventory' => $this->resourceConnection->getTableName('squareup_omni_inventory')],
                'square_inventory.product_id = e.entity_id',
                ['location_id']
            )->group('e.entity_id');

            $this->registry->register('join_squareup_omni_inventory', true);
        }
    }
}
