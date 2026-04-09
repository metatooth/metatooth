<?php

namespace Squareup\Omni\Model;

use Magento\Framework\Model\AbstractModel;

class Refunds extends AbstractModel
{
    /**
     * Define refunds statuses
     */
    const STATUS_PENDING_VALUE = 0;
    const STATUS_PENDING_LABEL = 'PENDING';

    const STATUS_APPROVED_VALUE = 1;
    const STATUS_APPROVED_LABEL = 'APPROVED';

    const STATUS_REJECTED_VALUE = 2;
    const STATUS_REJECTED_LABEL = 'REJECTED';

    const STATUS_FAILED_VALUE = 3;
    const STATUS_FAILED_LABEL = 'FAILED';

    protected function _construct()
    {
        parent::_init(\Squareup\Omni\Model\ResourceModel\Refunds::class);
    }
}
