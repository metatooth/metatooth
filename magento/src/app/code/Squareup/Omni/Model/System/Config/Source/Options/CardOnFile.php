<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/21/2018
 * Time: 1:36 PM
 */

namespace Squareup\Omni\Model\System\Config\Source\Options;

use Magento\Framework\Option\ArrayInterface;

class CardOnFile implements ArrayInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Squareup\Omni\Model\Card::DISALLOW_CARD_ON_FILE,
                'label' => 'Don\'t allow card on file payments'
            ],
            [
                'value' => \Squareup\Omni\Model\Card::ALLOW_CARD_ON_FILE,
                'label' => 'Allow credit card payments and card on file payments'
            ],
            [
                'value' => \Squareup\Omni\Model\Card::ALLOW_ONLY_CARD_ON_FILE,
                'label' => 'Allow only card on file payments'
            ],
        ];
    }
}
