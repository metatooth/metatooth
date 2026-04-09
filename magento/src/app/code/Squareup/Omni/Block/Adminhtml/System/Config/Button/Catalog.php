<?php
/**
 * SquareUp
 *
 * Catalog Adminhtml Block
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
 * Class Catalog
 */
class Catalog extends Field
{
    protected $_template = 'Squareup_Omni::system/config/button.phtml';

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
                'id'      => 'squareup_omni_catalog_run_catalog_sync',
                'label'   => __('Run'),
                'onclick' => "location.href='" . $this->getRequiredUrl() . "'"
            ]
        );

        return $button->toHtml();
    }

    /**
     * Get catalog sync url
     *
     * @return string
     */
    public function getRequiredUrl()
    {
        return $this->getUrl('square/sync/catalog');
    }
}
