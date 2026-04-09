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
use Squareup\Omni\Helper\Config;

/**
 * Class Oauth
 */
class RevokeOauth extends Field
{
    private $state;

    private $directoryList;

    private $config;

    public function __construct(
        DirectoryList $directoryList,
        Context $context,
        Config $config,
        array $data = []
    ) {
        $this->directoryList = $directoryList;
        $this->config = $config;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->state = hash('sha384', $this->_storeManager->getStore()->getBaseUrl() . time());
        $fh = new \SplFileObject($this->directoryList->getPath('var') . '/onlytoken.txt', 'w');
        $fh->fwrite($this->state);
        $fh = null;
        $this->setTemplate('Squareup_Omni::system/config/revoke_oauth.phtml');
    }

    /**
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
                'id'      => 'squareup_omni_general_application_revoke_oauth',
                'class'   => 'action secondary',
                'label'   => __('Revoke OAuth Token')
            ]
        );

        return $button->toHtml();
    }

    public function getRevokeUrl()
    {
        return $this->_urlBuilder->getUrl('square/oauth/revokeToken');
    }

    public function getScopeConfigValue($path)
    {
        return $this->config->getConfig($path);
    }
}
