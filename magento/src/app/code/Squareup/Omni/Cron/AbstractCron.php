<?php
/**
 * SquareUp
 *
 * AbstractCron Cron
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Cron;

use Squareup\Omni\Helper\Config;

/**
 * Class AbstractCron
 */
abstract class AbstractCron
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * AbstractCron constructor
     *
     * @param Config $config
     */
    public function __construct (Config $config) {
        ini_set('memory_limit', -1);

        $this->configHelper = $config;
    }

    /**
     * Execute cron
     *
     * @return mixed
     */
    abstract public function execute();
}
