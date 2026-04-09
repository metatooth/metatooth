<?php

namespace Squareup\Omni\Block\Adminhtml\Transaction\Renderer;

use Magento\Framework\DataObject;

/**
 * Render transaction type
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */
class Type extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
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
            case \Squareup\Omni\Model\Transactions::TYPE_CARD_VALUE:
                return \Squareup\Omni\Model\Transactions::TYPE_CARD_LABEL;
            default:
                return $type;
        }
    }
}
