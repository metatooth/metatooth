<?php

namespace Squareup\Omni\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Refunds extends AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            $this->_resources->getTableName('squareup_omni_refunds'),
            'id'
        );
    }

    public function emptyRefunds()
    {
        $connection = $this->_resources->getConnection();
        return $connection->truncateTable($this->_resources->getTableName('squareup_omni_refunds'));
    }
}
