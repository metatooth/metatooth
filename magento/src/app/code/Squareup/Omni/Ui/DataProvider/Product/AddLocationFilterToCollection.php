<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Squareup\Omni\Ui\DataProvider\Product;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;
use Magento\Framework\Model\ResourceModel\Iterator;
use Squareup\Omni\Model\LocationFactory;

/**
 * Class AddQuantityFilterToCollection
 */
class AddLocationFilterToCollection implements AddFilterToCollectionInterface
{
    /**
     * @var Iterator
     */
    private $iterator;
    /**
     * @var LocationFactory
     */
    private $locationFactory;

    /**
     * AddLocationFilterToCollection constructor.
     * @param Iterator $iterator
     * @param LocationFactory $locationFactory
     */
    public function __construct(
        Iterator $iterator,
        LocationFactory $locationFactory
    ) {
        $this->iterator = $iterator;
        $this->locationFactory = $locationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter(Collection $collection, $field, $condition = null)
    {
        if (isset($condition['eq'])) {
            $this->iterator->walk($collection->getSelect(), [[$this, 'filterByLocation']], [
                'location' => $condition['eq'],
                'collection' => $collection
            ]);
        }
    }

    public function filterByLocation($args)
    {
        $item = $args['collection']->getItemById($args['row']['entity_id']);

        $locations = explode(', ', $item['location_name']);

        $locationName = $this->locationFactory->create()->load($args['location'], 'square_id')->getName();

        if (!in_array($locationName, $locations)) {
            $args['collection']->removeItemByKey($args['row']['entity_id']);
        }
    }
}
