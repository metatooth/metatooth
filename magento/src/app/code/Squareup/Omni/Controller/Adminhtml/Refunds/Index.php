<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/7/2018
 * Time: 1:04 PM
 */

namespace Squareup\Omni\Controller\Adminhtml\Refunds;

use Magento\Framework\App\ResponseInterface;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Squareup_Omni::refunds';

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
        $this->_setActiveMenu('Squareup_Omni::refunds');
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Refunds'));
        $this->_view->renderLayout();
    }
}
