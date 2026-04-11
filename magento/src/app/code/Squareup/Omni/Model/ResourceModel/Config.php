<?php
/**
 * SquareUp
 *
 * Location ResourceModel
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Location
 */
class Config extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init(
            $this->_resources->getTableName('squareup_omni_config'),
            'config_id'
        );
    }
}
