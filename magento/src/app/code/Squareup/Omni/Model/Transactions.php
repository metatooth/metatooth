<?php

namespace Squareup\Omni\Model;

use Magento\Framework\Model\AbstractModel;

class Transactions extends AbstractModel
{
    /**
     * Define transaction types
     */
    const TYPE_CARD_VALUE = 0;
    const TYPE_CARD_LABEL = 'CARD';
    const TYPE_CASH_VALUE = 1;
    const TYPE_CASH_LABEL = 'CASH';

    protected function _construct()
    {
        parent::_init(\Squareup\Omni\Model\ResourceModel\Transactions::class);
    }
}
