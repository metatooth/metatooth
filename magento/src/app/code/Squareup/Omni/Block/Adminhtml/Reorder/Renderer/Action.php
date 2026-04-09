<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/24/2018
 * Time: 5:09 PM
 */

namespace Squareup\Omni\Block\Adminhtml\Reorder\Renderer;

use Magento\Backend\Block\Context;
use Magento\Framework\DataObjectFactory;

class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * Action constructor.
     * @param Context $context
     * @param DataObjectFactory $dataObjectFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        DataObjectFactory $dataObjectFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @var array
     */
    private $actions;

    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $this->actions = [];

        $reorderAction = [
            '@' => [
                'href' => $this->getUrl('square/transactions/refundTransaction', ['id' => $row->getId()]),
            ],
            '#' => __('Refund'),
        ];
        $this->addToActions($reorderAction);

        return $this->_actionsToHtml();
    }

    /**
     * Render options array as a HTML string
     *
     * @param array $actions
     * @return string
     */
    private function _actionsToHtml(array $actions = [])
    {
        $html = [];
        $attributesObject = $this->dataObjectFactory->create();

        if (empty($actions)) {
            $actions = $this->actions;
        }

        foreach ($actions as $action) {
            $attributesObject->setData($action['@']);
            $html[] = '<a ' . $attributesObject->serialize() . '>' . $action['#'] . '</a>';
        }
        return implode('', $html);
    }

    /**
     * Add one action array to all options data storage
     *
     * @param array $actionArray
     * @return void
     */
    public function addToActions($actionArray)
    {
        $this->actions[] = $actionArray;
    }
}
