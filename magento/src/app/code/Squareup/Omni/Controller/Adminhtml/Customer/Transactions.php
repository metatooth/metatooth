<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/24/2018
 * Time: 3:35 PM
 */

namespace Squareup\Omni\Controller\Adminhtml\Customer;

use Magento\Framework\App\ResponseInterface;

class Transactions extends \Magento\Customer\Controller\Adminhtml\Index
{
    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $this->initCurrentCustomer();
        $resultLayout = $this->resultLayoutFactory->create();
        return $resultLayout;
    }
}
