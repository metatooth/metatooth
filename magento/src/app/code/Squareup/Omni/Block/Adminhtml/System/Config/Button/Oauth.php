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
class Oauth extends Field
{
    private $state;

    /**
     * Square url
     *
     * @var string
     */
    public $squareUrl = 'https://connect.squareup.com/oauth2/';

    /**
     * Scope
     *
     * @var string
     */
    private $scope = 'GIFTCARDS_READ%20PAYMENTS_WRITE%20PAYMENTS_READ%20CUSTOMERS_READ%20CUSTOMERS_WRITE%20ORDERS_READ' .
                        '%20ORDERS_WRITE%20MERCHANT_PROFILE_READ%20ITEMS_READ%20ITEMS_WRITE%20INVENTORY_READ%20' .
                        'INVENTORY_WRITE';

    private $directoryList;

    public function __construct(
        DirectoryList $directoryList,
        Context $context,
        array $data = []
    ) {
        $this->directoryList = $directoryList;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->state = hash('sha384', $this->_storeManager->getStore()->getBaseUrl() . time());
        $fh = new \SplFileObject($this->directoryList->getPath('var') . '/onlytoken.txt', 'w');
        $fh->fwrite($this->state);
        $fh = null;
        $this->setTemplate('Squareup_Omni::system/config/oauth.phtml');
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
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id'      => 'squareup_omni_general_application_oauth',
                'label'   => __('Get OAuth Token'),
                'onclick' => "openConnection()"
            ]
        );

        return $button->toHtml();
    }

    /**
     * Build Oauth url
     *
     * @return string
     */
    public function buildOauthUrl()
    {
        return $this->squareUrl . 'authorize?scope=' . $this->scope . '&session=false' .
            '&state=' . $this->state . '&client_id=';
    }
}
