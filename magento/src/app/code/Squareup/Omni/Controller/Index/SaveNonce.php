<?php
/**
 * SquareUp
 *
 * SaveNonce Controller
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Controller\Index;

use Magento\Framework\App\Action\Action;

/**
 * Class SaveNonce
 */
class SaveNonce extends Action
{
    /**
     * Execute action based on request and return result
     *
     * @return mixed
     */
    public function execute()
    {
        return $this->getResponse()->setBody('nonce');
    }
}
