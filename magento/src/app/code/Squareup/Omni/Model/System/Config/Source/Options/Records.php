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
class Records implements ArrayInterface
{
    /**
     * Square Source
     */
    const SQUARE = 0;

    /**
     * Magento Source
     */
    const MAGENTO = 1;

    /**
     * To option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SQUARE,
                'label' => 'Square'
            ],
            [
                'value' => self::MAGENTO,
                'label' => 'Magento'
            ]
        ];
    }
}
