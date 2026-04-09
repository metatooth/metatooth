<?php
/**
 * SquareUp
 *
 * Inventory Adminhtml Controller
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Controller\Adminhtml\Inventory;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Squareup\Omni\Model\Inventory\Export;
use Squareup\Omni\Model\InventoryFactory;
use Squareup\Omni\Logger\Logger;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class Inventory
 */
class Location extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Squareup_Omni::location';

    /**
     * @var Export
     */
    private $export;

    private $inventoryFactory;

    private $logger;

    private $productFactory;

    private $dateTime;

    /**
     * Inventory constructor
     *
     * @param Action\Context $context
     * @param Export $export
     */
    public function __construct(
        Action\Context $context,
        Export $export,
        InventoryFactory $inventoryFactory,
        Logger $logger,
        ProductFactory $productFactory,
        DateTime $dateTime
    ) {
        $this->export = $export;
        $this->inventoryFactory = $inventoryFactory;
        $this->logger = $logger;
        $this->productFactory = $productFactory;
        $this->dateTime = $dateTime;
        parent::__construct($context);
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $params = $this->getRequest()->getParams();

        $data = [
            'product_id' => $params['product_id'],
            'location_id' => $params['location'],
            'status' => '',
            'quantity' => $params['qty'],
            'calculated_at' => $this->dateTime->gmtDate(),
            'received_at' => $this->dateTime->gmtDate()
        ];

        try {
             $this->inventoryFactory->create()
                 ->setData($data)
                 ->save();

             $product = $this->productFactory->create()->load($params['product_id']);
             $product->setName($product->getName())->save();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        $this->export->start([$params['product_id']], $params['location'], $params['qty']);

        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }

    /**
     * Check if user has permissions to access this controller
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
