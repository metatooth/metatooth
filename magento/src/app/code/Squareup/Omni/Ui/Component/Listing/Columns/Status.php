<?php

namespace Squareup\Omni\Ui\Component\Listing\Columns;

class Status extends \Magento\Ui\Component\Listing\Columns\Column {
    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ){
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource) {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item['status'] = $this->getStatusLabel($item['status']);
            }
        }

        return $dataSource;
    }

    public function getStatusLabel($code)
    {
        switch ($code) {
            case \Squareup\Omni\Model\Refunds::STATUS_PENDING_VALUE:
                return \Squareup\Omni\Model\Refunds::STATUS_PENDING_LABEL;
            case \Squareup\Omni\Model\Refunds::STATUS_APPROVED_VALUE:
                return \Squareup\Omni\Model\Refunds::STATUS_APPROVED_LABEL;
            case \Squareup\Omni\Model\Refunds::STATUS_REJECTED_VALUE:
                return \Squareup\Omni\Model\Refunds::STATUS_REJECTED_LABEL;
            case \Squareup\Omni\Model\Refunds::STATUS_FAILED_VALUE:
                return \Squareup\Omni\Model\Refunds::STATUS_FAILED_LABEL;
            default:
                return $code;
        }
    }
}
