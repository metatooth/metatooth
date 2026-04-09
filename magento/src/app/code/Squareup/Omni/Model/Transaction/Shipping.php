<?php
namespace Squareup\Omni\Model\Transaction;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

class Shipping extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'square_shipping';

    protected $_rateResultFactory;

    protected $_rateMethodFactory;

    protected $_appState;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Shipping constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Framework\App\State $appState
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\RequestInterface $request,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_appState = $appState;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['square_shipping' => 'square_shipping'];
    }

    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        if ($this->_appState->getAreaCode() == 'webapi_rest') {
            return false;
        }

        if($this->request->getModuleName() === 'sales' && $this->request->getActionName() === 'loadBlock') {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->_rateMethodFactory->create();

        $method->setCarrier('square_shipping');
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('square_shipping');
        $method->setMethodTitle($this->getConfigData('title'));

        $method->setPrice(0);
        $method->setCost(0);

        $result->append($method);

        return $result;
    }
}
