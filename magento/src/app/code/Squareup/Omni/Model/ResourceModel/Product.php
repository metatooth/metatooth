<?php
/**
 * SquareUp
 *
 * Product ResourceModel
 *
 * @category    Squareup
 * @package     Squareup_Omni
 * @copyright   2018
 * @author      SquareUp
 */

namespace Squareup\Omni\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Catalog\Model\Product\Action;
use Squareup\Omni\Logger\Logger;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetup;
use Squareup\Omni\Helper\Config;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Class Location
 */
class Product extends AbstractDb
{
    /**
     * @var ProductCollection
     */
    private $productCollection;

    /**
     * @var Action
     */
    private $action;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * Product constructor
     *
     * @param ProductCollection $productCollection
     * @param Action $action
     * @param Logger $logger
     * @param EavConfig $eavConfig
     * @param EavSetup $eavSetup
     * @param Config $config
     * @param ProductMetadataInterface $productMetadata
     * @param Context $context
     * @param null $connectionName
     */
    public function __construct(
        ProductCollection $productCollection,
        Action $action,
        Logger $logger,
        EavConfig $eavConfig,
        EavSetup $eavSetup,
        Config $config,
        ProductMetadataInterface $productMetadata,
        Context $context,
        $connectionName = null
    ) {
        $this->productCollection = $productCollection;
        $this->action = $action;
        $this->logger = $logger;
        $this->eavConfig = $eavConfig;
        $this->eavSetup = $eavSetup;
        $this->configHelper = $config;
        $this->productMetadata = $productMetadata;
        parent::__construct($context, $connectionName);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct(){}

    /**
     * Check if product exists
     *
     * @param $squareId
     *
     * @return string
     */
    public function productExists($squareId)
    {
        $column = 'entity_id';

        if ('Enterprise' == $this->productMetadata->getEdition()) {
            $column = 'row_id';
        }

        $conn = $this->_resources->getConnection();
        $select = $conn->select($column)
            ->from(
                [
                    'p' => $this->_resources->getTableName('catalog_product_entity_varchar')
                ],
                new \Zend_Db_Expr($column)
            )
            ->join(
                [
                    'st' => $this->_resources->getTableName('eav_attribute')],
                'st.attribute_id = p.attribute_id',
                []
            )
            ->where('st.attribute_code = ?', 'square_id')
            ->where('st.entity_type_id = ?', 4)
            ->where('value = ?', $squareId);
        $id = $conn->fetchOne($select);

        return $id;
    }

    /**
     * Delete products
     *
     * @param $ids
     *
     * @return bool
     */
    public function deleteProducts($ids)
    {
        $conn = $this->_resources->getConnection();
        $productEntityTable = $this->_resources->getTableName('catalog_product_entity');
        if ($ids) {
            $conn->query(
                $conn->quoteInto("DELETE FROM `{$productEntityTable}` WHERE `entity_id` IN (?)", $ids)
            );
        }

        return true;
    }

    /**
     * Reset products
     *
     * @param null $ids
     */
    public function resetProducts($ids = null)
    {
        $square = \Squareup\Omni\Model\System\Config\Source\Options\Records::SQUARE;

        if (null === $ids) {
            $productCollection = $this->productCollection->create();
            $productIds = $productCollection->getAllIds();
        } else {
            $productIds = $ids;
        }

        try {
            $this->action->updateAttributes(
                $productIds,
                ['square_id' => null],
                0
            );
            $this->action->updateAttributes(
                $productIds,
                ['square_variation_id' => null],
                0
            );
            if (null === $ids && $square == $this->configHelper->getSor()) {
                $this->resetAttributeOptions();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
        }
    }

    /**
     * Reset attribute options
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetAttributeOptions()
    {
        try {
            $attr = $this->eavConfig->getAttribute(
                'catalog_product',
                \Squareup\Omni\Model\Square::SQUARE_VARIATION_ATTR
            );
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
        }

        $options = $attr->getSource()->getAllOptions();
        array_shift($options);

        foreach ($options as $option) {
            $options['delete'][$option['value']] = true;
            $options['value'][$option['value']] = true;
        }

        $this->eavSetup->addAttributeOption($options);
    }

    /**
     * Check if sku exists
     *
     * @param $sku
     *
     * @return string
     */
    public function skuExists($sku)
    {
        $conn = $this->_resources->getConnection();
        $select = $conn->select()
            ->from(
                [
                    'p' => $this->_resources->getTableName('catalog_product_entity')
                ],
                new \Zend_Db_Expr('entity_id')
            )
            ->where('sku = ?', $sku);
        $id = $conn->fetchOne($select);

        return $id;
    }

    /**
     * Get product locations
     *
     * @param $productId
     *
     * @return array
     */
    public function getProductLocations($productId)
    {
        $conn = $this->_resources->getConnection();
        $select = $conn->select()
            ->from(
                [
                    'p' => $this->_resources->getTableName('squareup_omni_inventory')
                ],
                new \Zend_Db_Expr('location_id')
            )
            ->where('product_id = ?', $productId);
        $ids = $conn->fetchCol($select);

        return $ids;
    }

    public function getRowId($product, $entityId)
    {
        if ($product->getEntityIdField() == $product->getLinkField()) {
            return $entityId;
        }

        $select = $this->getConnection()->select();
        $tableName = $this->_resources->getTableName('catalog_product_entity');
        $select->from($tableName, [$product->getLinkField()])
            ->where('entity_id = ?', $entityId);
        return $this->getConnection()->fetchOne($select);
    }

    public function getProductData($squareVariationId)
    {
        $column = 'entity_id';

        if ('Enterprise' == $this->productMetadata->getEdition()) {
            $column = 'row_id';
        }

        $productEntity = $this->_resources->getTableName('catalog_product_entity');
        $productEntityVarchar = $this->_resources->getTableName('catalog_product_entity_varchar');
        $select = $this->getConnection()->select()
            ->from(['main' => $productEntity], ['main.sku', 'main.entity_id'])
            ->join(
                ['varchar' => $productEntityVarchar],
                'main.'.$column.'=varchar.'.$column,
                ''
            )->where("varchar.value = '$squareVariationId'");

        return $this->getConnection()->fetchRow($select);
    }
}
