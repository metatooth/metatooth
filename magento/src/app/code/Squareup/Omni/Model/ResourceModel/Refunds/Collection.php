<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Squareup\Omni\Model\ResourceModel\Refunds;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Squareup\Omni\Model\Refunds::class,
            \Squareup\Omni\Model\ResourceModel\Refunds::class
        );
    }
}
