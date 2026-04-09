<?php
/**
 * SquareUp
 *
 * Location ResourceModel
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Location
 */
class Location extends AbstractDb
{
    private $logger;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Squareup\Omni\Logger\Logger $logger,
        $connectionName = null
    )
    {
        parent::__construct($context, $connectionName);

        $this->logger = $logger;
    }

    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init(
            $this->_resources->getTableName('squareup_omni_location'),
            'id'
        );
    }

    public function emptyLocations()
    {
        $connection = $this->_resources->getConnection();
        return $connection->truncateTable($this->_resources->getTableName('squareup_omni_location'));
    }

    public function addWebhookTimeToLocation($locationId, $time)
    {
        try {
            $connection = $this->_resources->getConnection();
            $tablename = $this->_resources->getTableName('squareup_omni_location');
            $connection->update($tablename, ["webhook_time" => $time] ,["square_id=?" => $locationId]);
        } catch (\Exception $e) {
            $this->logger->error('There was an error trying to save the webhook');
            $this->logger->error($e->__toString());
            return false;
        }

        return true;
    }

    public function getWebhookTimeByLocationId($locationId)
    {
        $connection = $this->_resources->getConnection();
        $tablename = $this->_resources->getTableName('squareup_omni_location');
        $query = $connection->select()->from($tablename, 'webhook_time')->where("square_id=?", $locationId);
        $data = $connection->fetchRow($query);

        return (isset($data['webhook_time']))? $data['webhook_time'] : null ;
    }
}
