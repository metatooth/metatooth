<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/7/2018
 * Time: 1:04 PM
 */

namespace Squareup\Omni\Controller\Adminhtml\Transactions;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;

use Squareup\Omni\Helper\Data as OmniDataHelper;
use Squareup\Omni\Logger\Logger as OmniLogHelper;

class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Squareup_Omni::transactions';

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Squareup_Omni::transactions');
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Transactions'));
        $this->_view->renderLayout();
    }
}
