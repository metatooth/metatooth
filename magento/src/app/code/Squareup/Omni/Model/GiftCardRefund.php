<?php
/**
 * SquareUp
 *
 * GiftCard Model
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\HTTP\Client\Curl;
use Squareup\Omni\Helper\Config;

/**
 * Class GiftCardRefund
 */
class GiftCardRefund extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(\Squareup\Omni\Model\ResourceModel\GiftCardRefund::class);
    }
}
