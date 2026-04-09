<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/15/2018
 * Time: 10:54 AM
 */

namespace Squareup\Omni\Observer\System;

use Magento\Framework\Event\Observer;
use Magento\Framework\Registry;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;
use Magento\Framework\App\Cache\Frontend\Pool as CacheFrontendPool;

class AfterConfigSave implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var CacheTypeList
     */
    private $cacheTypeList;
    /**
     * @var CacheFrontendPool
     */
    private $cacheFrontendPool;

    /**
     * AfterConfigSave constructor.
     * @param Registry $registry
     * @param CacheTypeList $cacheTypeList
     * @param CacheFrontendPool $cacheFrontendPool
     */
    public function __construct(
        Registry $registry,
        CacheTypeList $cacheTypeList,
        CacheFrontendPool $cacheFrontendPool
    ) {
        $this->registry = $registry;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    /**
     * @param Observer $observer
     * @return BeforeConfigSave
     */
    public function execute(Observer $observer)
    {
        if (!empty($this->registry->registry('squareup_omni_clear_fpcache')) &&
            $this->registry->registry('squareup_omni_clear_fpcache')) {
            $types = [
                'config',
                'layout',
                'block_html',
                'collections',
                'reflection',
                'db_ddl',
                'eav',
                'config_integration',
                'config_integration_api',
                'full_page',
                'translate',
                'config_webservice'
            ];
            foreach ($types as $type) {
                $this->cacheTypeList->cleanType($type);
            }
            foreach ($this->cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }

            $this->registry->unregister('squareup_omni_clear_fpcache');
        }
    }
}
