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
class Status extends AbstractRenderer
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
        $type = $this->_getValue($row);
        switch ($type) {
            case \Squareup\Omni\Model\Refunds::STATUS_PENDING_VALUE:
                return \Squareup\Omni\Model\Refunds::STATUS_PENDING_LABEL;
            case \Squareup\Omni\Model\Refunds::STATUS_APPROVED_VALUE:
                return \Squareup\Omni\Model\Refunds::STATUS_APPROVED_LABEL;
            case \Squareup\Omni\Model\Refunds::STATUS_REJECTED_VALUE:
                return \Squareup\Omni\Model\Refunds::STATUS_REJECTED_LABEL;
            case \Squareup\Omni\Model\Refunds::STATUS_FAILED_VALUE:
                return \Squareup\Omni\Model\Refunds::STATUS_FAILED_LABEL;
            default:
                return $type;
        }
    }
}
