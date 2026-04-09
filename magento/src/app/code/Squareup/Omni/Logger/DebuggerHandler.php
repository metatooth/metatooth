<?php
/**
 * SquareUp
 *
 * Handler Log
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Logger;

use Magento\Framework\Logger\Handler\Base;

/**
 * Class Handler
 */
class DebuggerHandler extends Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Debugger::INFO;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/squareup_debugger.log';
}
