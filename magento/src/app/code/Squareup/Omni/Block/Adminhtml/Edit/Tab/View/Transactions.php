<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/24/2018
 * Time: 5:41 PM
 */

namespace Squareup\Omni\Block\Adminhtml\Edit\Tab\View;

use Squareup\Omni\Helper\Data as DataHelper;
use Squareup\Omni\Model\Transactions as SquareTransactions;

class Transactions extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Squareup\Omni\Model\ResourceModel\Transactions\Grid\CollectionFactory
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
     * @param \Squareup\Omni\Model\ResourceModel\Transactions\Grid\CollectionFactory $collectionFactory
     * @param DataHelper $dataHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Squareup\Omni\Model\ResourceModel\Transactions\Grid\CollectionFactory $collectionFactory,
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
        $this->setId('customer_view_transactions_grid');
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
                'header' => __('Square Transaction Id'),
                'align'  => 'left',
                'type'   => 'text',
                'index'  => 'square_id',
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
            'location_id',
            [
                'header'    => __('Location'),
                'align'     => 'left',
                'type'      => 'options',
                'renderer'  => \Squareup\Omni\Block\Adminhtml\Transaction\Renderer\Location::class,
                'index'     => 'location_id',
                'options'   => $this->dataHelper->getLocationsOptionArray(),
                'filter_condition_callback' => [$this, 'filterLocation'],
            ]
        );

        $this->addColumn(
            'customer_square_id',
            [
                'header' => __('Customer Id'),
                'align'  => 'left',
                'type'   => 'text',
                'index'  => 'customer_square_id',
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
            'type',
            [
                'header'    => __('Type'),
                'align'     => 'left',
                'type'      => 'options',
                'renderer'  => \Squareup\Omni\Block\Adminhtml\Transaction\Renderer\Type::class,
                'index'     => 'type',
                'options'   =>  [
                    \Squareup\Omni\Model\Transactions::TYPE_CARD_VALUE
                    => \Squareup\Omni\Model\Transactions::TYPE_CARD_LABEL
                ]
            ]
        );

        $this->addColumn(
            'card_brand',
            [
                'header' => __('Card Brand'),
                'align'  => 'left',
                'type'   => 'text',
                'index'  => 'card_brand',
            ]
        );

        $this->addColumn(
            'note',
            [
                'header' => __('Note'),
                'align'  => 'left',
                'type'   => 'text',
                'index'  => 'note',
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

        $this->addColumn(
            'action',
            [
                'header'  =>  __('Action'),
                'width'   => '100',
                'type'    => 'action',
                'getter'  => 'getId',
                'actions' => [
                    [
                        'caption' => __('Refund'),
                        'url'     => ['base'=> 'square/transactions/refundTransaction'],
                        'field'   => 'id'
                    ]
                ],
                'filter'    => false,
                'is_system' => true,
                'sortable'  => false,
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
}
