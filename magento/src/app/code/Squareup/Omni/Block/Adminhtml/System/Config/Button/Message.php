<?php
/**
 * SquareUp
 *
 * Message Adminhtml Block
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Block\Adminhtml\System\Config\Button;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Squareup\Omni\Model\Catalog\ImagesFactory;

/**
 * Class Message
 */
class Message extends Field
{
    /**
     * @var ImagesFactory
     */
    private $imagesFactory;

    /**
     * Message constructor.
     * @param Context $context
     * @param ImagesFactory $imagesFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        ImagesFactory $imagesFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->imagesFactory = $imagesFactory;
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $htmlId = $element->getHtmlId();
        $id = str_replace('squareup_omni_', '', $htmlId);
        switch ($id) {
            case 'general_sandbox_documentation':
                $this->setTemplate('Squareup_Omni::system/config/sandbox_documentation.phtml');
                break;
            case 'oauth_settings_oauth_message':
                $this->setTemplate('Squareup_Omni::system/config/oauth_message.phtml');
                break;
            case 'oauth_settings_redirect_url':
                $this->setTemplate('Squareup_Omni::system/config/redirect.phtml');
                break;
            case 'webhooks_settings_webhook_url':
                $this->setTemplate('Squareup_Omni::system/config/webhook.phtml');
                break;
            case 'catalog_images_size':
                $this->setTemplate('Squareup_Omni::system/config/images_size.phtml');
                break;
            default:
                $this->setTemplate('Squareup_Omni::system/config/message.phtml');
        }

        return $this->_toHtml();
    }

    public function getElementHtml()
    {
        $element = $this->getLayout()->createBlock(\Magento\Framework\View\Element\Template::class);
        return $element->toHtml();
    }

    public function getCatalogImageModel()
    {
        return $this->imagesFactory->create();
    }
}
