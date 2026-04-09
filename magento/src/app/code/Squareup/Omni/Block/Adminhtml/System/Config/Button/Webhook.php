<?php
/**
 * SquareUp
 *
 * Redirect Adminhtml Block
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Block\Adminhtml\System\Config\Button;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Redirect
 */
class Webhook extends Field
{
    /**
     * Field template
     *
     * @var string
     */
    protected $_template = 'Squareup_Omni::system/config/webhook.phtml';

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Get redirect url
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWebhookUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl('web') . 'squareupomni/hooks/notify/';
    }
}
