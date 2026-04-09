<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Squareup\Omni\Ui\DataProvider\Product;

use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AddFieldToCollectionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\ProductFactory as CatalogProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Framework\App\ResourceConnection;

/**
 * Class AddQuantityFieldToCollection
 */
class AddLocationFieldToCollection implements AddFieldToCollectionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var CatalogProductFactory
     */
    private $catalogProductFactory;
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var Iterator
     */
    private $iterator;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * AddLocationFieldToCollection constructor.
     * @param RequestInterface $request
     * @param CatalogProductFactory $catalogProductFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Iterator $iterator
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        RequestInterface $request,
        CatalogProductFactory $catalogProductFactory,
        ProductCollectionFactory $productCollectionFactory,
        Iterator $iterator,
        ResourceConnection $resourceConnection
    ) {
        $this->request = $request;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->iterator = $iterator;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function addField(Collection $collection, $field, $alias = null)
    {
        $oldCollection = clone $collection;

        $oldCollection->getSelect()
            ->joinLeft(
                ['at_squareup_omni_inventory' =>
                    $this->resourceConnection->getTableName('squareup_omni_inventory')
                ],
                'at_squareup_omni_inventory.product_id=e.entity_id',
                []
            )
            ->joinLeft(
                ['at_squareup_omni_location' =>
                    $this->resourceConnection->getTableName('squareup_omni_location')],
                'at_squareup_omni_location.square_id=at_squareup_omni_inventory.location_id',
                ['location_name' => 'at_squareup_omni_location.name']
            )
            ->where('at_squareup_omni_inventory.product_id IS NULL OR at_squareup_omni_inventory.product_id IS NOT NULL')
            ->where('at_squareup_omni_location.square_id IS NULL OR at_squareup_omni_location.square_id IS NOT NULL')
            ->where('at_squareup_omni_location.status = 1');

        $this->iterator->walk($oldCollection->getSelect(), [[$this, "buildLocationsString"]], [
            'collection' => $collection
        ]);
    }

    public function buildLocationsString($args)
    {
        $newItem = $args['collection']->getItemById($args['row']['entity_id']);
        if (!empty($newItem)) {
            if (!empty($newItem->getLocationName())) {
                $newItem->setLocationName($newItem->getLocationName() . ', ' . $args['row']['location_name']);
            } else {
                $newItem->setLocationName($args['row']['location_name']);
            }
        }
    }
}
