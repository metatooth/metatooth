<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/24/2018
 * Time: 5:41 PM
 */

namespace Squareup\Omni\Block\Adminhtml\Edit\Tab\View;

use Squareup\Omni\Helper\Data as DataHelper;
use Squareup\Omni\Model\Refunds as SquareRefunds;

class Refunds extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Squareup\Omni\Model\ResourceModel\Refunds\Grid\CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Squareup\Omni\Model\ResourceModel\Refunds\Grid\CollectionFactory $collectionFactory
     * @param DataHelper $dataHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Squareup\Omni\Model\ResourceModel\Refunds\Grid\CollectionFactory $collectionFactory,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
        $this->dataHelper = $dataHelper;
    }

    /**
     * Initialize the orders grid.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customer_view_refunds_grid');
        $this->setDefaultSort('created_at', 'desc');
        $this->setSortable(false);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'align'  => 'left',
                'type'   => 'number',
                'index'  => 'id',
                'width'  => '50px',
            ]
        );

        $this->addColumn(
            'square_id',
            [
                'header' => __('Square Refund Id'),
                'align'  => 'left',
                'type'   => 'text',
                'index'  => 'square_id',
            ]
        );

        $this->addColumn(
            'location_id',
            [
                'header'    => __('Location'),
                'align'     => 'left',
                'type'      => 'options',
                'renderer'  => \Squareup\Omni\Block\Adminhtml\Refunds\Renderer\Location::class,
                'index'     => 'location_id',
                'options'   => $this->dataHelper->getLocationsOptionArray(),
                'filter_condition_callback' => [$this, 'filterLocation'],
            ]
        );

        $this->addColumn(
            'transaction_id',
            [
                'header' => __('Transaction Id'),
                'align'  => 'left',
                'type'   => 'text',
                'index'  => 'transaction_id',
            ]
        );

        $this->addColumn(
            'tender_id',
            [
                'header' => __('Tender Id'),
                'align'  => 'left',
                'type'   => 'text',
                'index'  => 'tender_id',
            ]
        );

        $this->addColumn(
            'amount',
            [
                'header'=> __('Amount'),
                'type'  => 'price',
                'currency_code' => $this->_storeManager->getStore()->getDefaultCurrencyCode(),
                'index' => 'amount',
            ]
        );

        $this->addColumn(
            'processing_fee_amount',
            [
                'header'=> __('Processing Fee Amount'),
                'type'  => 'price',
                'currency_code' => $this->_storeManager->getStore()->getDefaultCurrencyCode(),
                'index' => 'processing_fee_amount',
            ]
        );
        $this->addColumn(
            'reason',
            [
                'header' => __('Reason'),
                'align'  => 'left',
                'type'   => 'text',
                'index'  => 'reason',
            ]
        );

        $this->addColumn(
            'status',
            [
                'header'    => __('Status'),
                'align'     => 'left',
                'type'      => 'options',
                'renderer'  => \Squareup\Omni\Block\Adminhtml\Refunds\Renderer\Status::class,
                'index'     => 'status',
                'options'   =>  [
                    \Squareup\Omni\Model\Refunds::STATUS_PENDING_VALUE
                    => \Squareup\Omni\Model\Refunds::STATUS_PENDING_LABEL,
                    \Squareup\Omni\Model\Refunds::STATUS_APPROVED_VALUE
                    => \Squareup\Omni\Model\Refunds::STATUS_APPROVED_LABEL,
                    \Squareup\Omni\Model\Refunds::STATUS_REJECTED_VALUE
                    => \Squareup\Omni\Model\Refunds::STATUS_REJECTED_LABEL,
                    \Squareup\Omni\Model\Refunds::STATUS_FAILED_VALUE
                    => \Squareup\Omni\Model\Refunds::STATUS_FAILED_LABEL
                ],
                'filter_condition_callback' => [$this, 'filterStatus'],
            ]
        );

        $this->addColumn(
            'created_at',
            [
                'header' => __('Created at'),
                'align'  => 'center',
                'type'   => 'datetime',
                'index'  => 'created_at',
                'width'  => '150px',
            ]
        );

        //export to csv and excel
        $this->addExportType('*/*/exportCsv', __('CSV'));
        $this->addExportType('*/*/exportExcel', __('Excel XML'));

        return parent::_prepareColumns();
    }

    /**
     * Grid url.
     */
    public function getGridUrl()
    {
        return $this->getUrl('square/customer/transactions', ['_current'=>true]);
    }

    /**
     * Filter location by select
     *
     * @param $collection
     * @param $column
     * @return object
     */
    private function filterLocation($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $collection->addFieldToFilter('location_id', $value);

        return $this;
    }

    /**
     * Filter status
     *
     * @return object
     */
    private function filterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $collection->addFieldToFilter('status', $value);

        return $this;
    }
}
