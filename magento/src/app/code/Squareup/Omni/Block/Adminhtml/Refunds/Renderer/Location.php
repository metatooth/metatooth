<?php

namespace Squareup\Omni\Block\Adminhtml\Refunds\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Squareup\Omni\Model\LocationFactory;

/**
 * Render transaction type
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */
class Location extends AbstractRenderer
{
    /**
     * @var LocationFactory
     */
    private $locationFactory;

    /**
     * Location constructor.
     * @param \Magento\Backend\Block\Context $context
     * @param LocationFactory $locationFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        LocationFactory $locationFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->locationFactory = $locationFactory;
    }

    /**
     * Renders grid column
     *
     * @param DataObject $row
     * @return  string
     */
    public function render(DataObject $row)
    {
        $squareId = $this->_getValue($row);
        $location = $this->locationFactory->create()->load($squareId, 'square_id');
        if ($location && $location->getId()) {
            return $location->getName();
        }

        return $squareId;
    }
}
