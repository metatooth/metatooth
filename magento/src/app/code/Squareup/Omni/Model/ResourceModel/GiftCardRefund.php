<?php
/**
 * SquareUp
 *
 * GiftCard ResourceModel
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class GiftCardRefund
 */
class GiftCardRefund extends AbstractDb
{
    const TABLE_NAME = 'squareup_omni_giftcard_refunds';

    const ENTITY_ID = 'entity_id';

    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init(
            $this->_resources->getTableName(self::TABLE_NAME),
            self::ENTITY_ID
        );
    }
}
