<?php
/**
 * SquareUp
 *
 * Oauth Adminhtml Block
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Block\Adminhtml\System\Config\Button;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Filesystem\DirectoryList;

/**
 * Class Oauth
 */
class Webhooks extends Field
{
    private $directoryList;

    public function __construct(
        DirectoryList $directoryList,
        Context $context,
        array $data = []
    ) {
        $this->directoryList = $directoryList;
        parent::__construct($context, $data);
        $this->setTemplate('Squareup_Omni::system/config/webhook_button.phtml');
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Generate button html
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id'      => 'squareup_omni_webhooks_settings_webhook_button',
                'label'   => __('Subscribe webhooks'),
                'onclick' => "submitWebhooks()"
            ]
        );

        return $button->toHtml();
    }

    /**
     * Get webhooks subscribe url
     *
     * @return string
     */
    public function getWebhooksUrl()
    {
        return $this->_urlBuilder->getUrl("square/webhooks/subscribe");
    }
}
