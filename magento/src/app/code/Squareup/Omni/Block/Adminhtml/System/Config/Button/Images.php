<?php
/**
 * SquareUp
 *
 * Images Adminhtml Block
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
 * Class Images
 */
class Images extends Field
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Squareup_Omni::system/config/button.phtml');
    }
    /**
     * @param AbstractElement $element
     *
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
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id'      => 'squareup_omni_catalog_run_images_sync',
                'label'   => __('Run'),
                'onclick' => "location.href='" . $this->getRequiredUrl() . "'"
            ]
        );

        return $button->toHtml();
    }

    /**
     * Get images sync url
     *
     * @return string
     */
    public function getRequiredUrl()
    {
        return $this->getUrl('square/sync/images');
    }
}
