<?php
/**
 * SquareUp
 *
 * Location Source Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\System\Config\Source\Options;

use Magento\Framework\Option\ArrayInterface;
use Squareup\Omni\Model\ResourceModel\Location\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Class Location
 */
class Location extends AbstractSource implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    private $locationCollectionFactory;

    /**
     * @var array
     */
    private $optionsArray = [];

    /**
     * Location constructor
     *
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->locationCollectionFactory = $collectionFactory;
    }

    /**
     * To option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $locations = $this->locationCollectionFactory->create()
            ->addFieldToFilter('status', ['eq' => 1])
            ->addFieldToFilter('cc_processing', ['eq' => 1]);


        $this->optionsArray = [];

        if (!empty($locations)) {
            foreach ($locations as $location) {
                $this->optionsArray[] = [
                    'label' => $location->getName(),
                    'value' => $location->getSquareId()
                ];
            }

            array_unshift(
                $this->optionsArray,
                [
                    'value' => '',
                    'label' => __('Please select location')
                ]
            );
        }

        return $this->optionsArray;
    }

    public function getAllOptions()
    {
        return $this->toOptionArray();
    }
}
