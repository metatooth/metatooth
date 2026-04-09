<?php
/**
 * SquareUp
 *
 * Records Source Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\System\Config\Source\Options;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Records
 */
class Type implements ArrayInterface
{
    /**
     * To option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Squareup\Omni\Model\Transactions::TYPE_CARD_VALUE,
                'label' => \Squareup\Omni\Model\Transactions::TYPE_CARD_LABEL
            ],
            [
                'value' => \Squareup\Omni\Model\Transactions::TYPE_CASH_VALUE,
                'label' => \Squareup\Omni\Model\Transactions::TYPE_CASH_LABEL
            ]
        ];
    }
}
