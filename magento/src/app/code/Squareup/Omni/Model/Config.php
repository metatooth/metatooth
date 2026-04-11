<?php
/**
 * SquareUp
 *
 * Location Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Location
 */
class Config extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(\Squareup\Omni\Model\ResourceModel\Config::class);
    }
}
