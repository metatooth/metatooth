<?php
/**
 * SquareUp
 *
 * Callback Block
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Block;

use Magento\Framework\View\Element\Template;
use Squareup\Omni\Model\ResourceModel\Location\CollectionFactory as LocationCollection;
use Magento\Framework\Data\Form\FormKey;

/**
 * Class Callback
 */
class Callback extends Template
{
    /**
     * @var LocationCollection
     */
    private $locationCollection;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * Callback constructor
     *
     * @param LocationCollection $locationCollection
     * @param FormKey $formKey
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        LocationCollection $locationCollection,
        FormKey $formKey,
        Template\Context $context,
        array $data = []
    ) {
        $this->locationCollection = $locationCollection;
        $this->formKey = $formKey;
        parent::__construct($context, $data);
    }

    /**
     * Get locations
     *
     * @return \Squareup\Omni\Model\ResourceModel\Location\Collection
     */
    public function getLocations()
    {
        $locations = $this->locationCollection->create()
            ->addFieldToFilter('status', ['eq' => 1])
            ->addFieldToFilter('cc_processing', ['eq' => 1]);

        return $locations;
    }

    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
}
