<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/9/2018
 * Time: 4:03 PM
 */

namespace Squareup\Omni\Block;

use Magento\Framework\View\Element\Template;
use Squareup\Omni\Model\Card;

class CustomerCards extends Template
{
    /**
     * CustomerCards constructor.
     * @param Template\Context $context
     * @param Card $card
     * @param \Squareup\Omni\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Card $card,
        \Squareup\Omni\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        if ($helper->haveSavedCards() && $helper->isSaveOnFileEnabled()) {
            $card->checkCcUpdates();
        }
    }
}
