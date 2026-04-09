<?php

namespace Squareup\Omni\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Transactions extends AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            $this->_resources->getTableName('squareup_omni_transaction'),
            'id'
        );
    }

    public function emptyTransactions()
    {
        $connection = $this->_resources->getConnection();
        return $connection->truncateTable($this->_resources->getTableName('squareup_omni_transaction'));
    }

    public function transactionExists($locationId, $transactionId)
    {
        $conn = $this->_resources->getConnection();
        $select = $conn->select('id')
            ->from(
                [
                    'p' => $this->_resources->getTableName('squareup_omni_transaction')
                ],
                new \Zend_Db_Expr('id')
            )
            ->where('square_id = ?', $transactionId)
            ->where('location_id = ?', $locationId);
        $id = $conn->fetchOne($select);

        return $id;
    }

    public function loadNoteBySquareId($squareId)
    {
        $conn = $this->_resources->getConnection();
        $select = $conn->select('note')
            ->from(
                [
                    'p' => $this->_resources->getTableName('squareup_omni_transaction')
                ],
                new \Zend_Db_Expr('note')
            )
            ->where('square_id = ?', $squareId);
        $id = $conn->fetchOne($select);

        return $id;
    }
}
