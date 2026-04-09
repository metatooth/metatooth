<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/21/2018
 * Time: 10:28 AM
 */

namespace Squareup\Omni\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class TransactionActions extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $transactionId = $this->context->getFilterParam('id');

            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')]['refund'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'square/transactions/refundTransaction',
                        ['id' => $item['id'], 'transaction' => $transactionId]
                    ),
                    'label' => __('Refund'),
                    'hidden' => false,
                ];
            }
        }

        return $dataSource;
    }
}
