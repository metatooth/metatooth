<?php

namespace Squareup\Omni\Model\Config;

use Squareup\Omni\Model\ResourceModel\Config\CollectionFactory as ConfigCollection;
use Squareup\Omni\Model\ResourceModel\Config as ConfigResource;
use Squareup\Omni\Model\ConfigFactory;
use Squareup\Omni\Logger\Logger;

class Config extends \Magento\Framework\App\Config\Value
{
    private $configCollection;
    /**
     * @var ConfigResource
     */
    private $configResource;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param ConfigResource $configResource
     * @param ConfigFactory $configFactory
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        ConfigCollection $configCollection,
        ConfigResource $configResource,
        ConfigFactory $configFactory,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);

        $this->configCollection = $configCollection;
        $this->configResource = $configResource;
        $this->configFactory = $configFactory;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\App\Config\Value
     */
    public function beforeSave()
    {
        if (strpos($this->getData('path'), 'squareup_omni') === false) {
            return parent::beforeSave();
        }

        $scope = $this->getData('scope');

        switch ($scope) {
            case 'websites':
                $scope = 'website';
                break;
            case 'stores':
                $scope = 'store';
                break;
            default:
                break;
        }

        /** @var \Squareup\Omni\Model\ResourceModel\Config\Collection $collection */
        $collection = $this->configCollection->create()
            ->addFieldToFilter('scope', ['eq' => $scope])
            ->addFieldToFilter('scope_id', ['eq' => $this->getData('scope_id')])
            ->addFieldToFilter('path', ['eq' => $this->getData('path')]);

        if ($collection->count()) {
            $configItem = $collection->getFirstItem();
            $config = $this->configFactory->create();
            $this->configResource->load($config, $configItem->getConfigId(), 'config_id');
        } else {
            $config = $this->configFactory->create();
        }

        $config->setScope($scope);
        $config->setScopeId($this->getData('scope_id'));
        $config->setpath($this->getData('path'));
        $config->setValue($this->getData('value'));

        try {
            $this->configResource->save($config);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return parent::beforeSave();
    }

    public function _afterLoad()
    {
        if (($path = $this->getData('path')) && (strpos($path, 'squareup_omni') !== false)) {

            $collection = $this->configCollection->create()
                ->addFieldToFilter('path', ['eq' => $this->getData('path')]);

            if ($this->getData('website')) {
                $collection->addFieldToFilter('scope', ['eq' => 'website']);
                $collection->addFieldToFilter('scope_id', ['eq' => $this->getData('website')]);
            }

            if ($this->getData('store')) {
                $collection->addFieldToFilter('scope', ['eq' => 'store']);
                $collection->addFieldToFilter('scope_id', ['eq' => $this->getData('store')]);
            }

            if ($collection->count()) {
                $this->setData('value', $collection->getFirstItem()->getValue());
            } else {
                $this->setData('value', null);
            }
        }

        return parent::_afterLoad();
    }
}
