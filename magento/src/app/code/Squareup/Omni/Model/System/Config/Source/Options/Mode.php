<?php
/**
 * SquareUp
 *
 * Mode Source Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\System\Config\Source\Options;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Mode
 */
class Mode implements ArrayInterface
{
    /**
     * Production mode
     */
    const PRODUCTION_ENV = 'prod';

    /**
     * Sandbox mode
     */
    const SANDBOX_ENV = 'sandbox';

    /**
     * To option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::PRODUCTION_ENV,
                'label' => 'Production'
            ],
            [
                'value' => self::SANDBOX_ENV,
                'label' => 'Sandbox'
            ]
        ];
    }
}
