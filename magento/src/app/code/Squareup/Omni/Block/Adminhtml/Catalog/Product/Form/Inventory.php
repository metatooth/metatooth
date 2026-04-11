<?php
namespace Squareup\Omni\Block\Adminhtml\Catalog\Product\Form;

use Magento\Backend\Block\Template\Context;
use Squareup\Omni\Model\ResourceModel\Inventory\CollectionFactory as InventoryCollection;
use Squareup\Omni\Model\ResourceModel\Location\CollectionFactory as LocationCollection;
use Magento\Catalog\Model\ProductFactory as CatalogProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ResourceConnection;

class Inventory extends \Magento\Backend\Block\Template
{
    /**
     * Block template.
     *
     * @var string
     */
    protected $_template = 'product/form/inventory.phtml';

    /**
     * @var
     */
    private $inventory;

    /**
     * @var InventoryCollection
     */
    private $inventoryCollection;

    /**
     * @var LocationCollection
     */
    private $locationCollection;

    /**
     * @var CatalogProductFactory
     */
    private $catalogProductFactory;
    /**
     * @var Configurable
     */
    private $configurable;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Inventory constructor
     *
     * @param Context $context
     * @param InventoryCollection $inventoryCollection
     * @param LocationCollection $locationCollection
     * @param CatalogProductFactory $catalogProductFactory
     * @param Configurable $configurable
     * @param ResourceConnection $resourceConnection
     * @param array $data
     */
    public function __construct(
        Context $context,
        InventoryCollection $inventoryCollection,
        LocationCollection $locationCollection,
        CatalogProductFactory $catalogProductFactory,
        Configurable $configurable,
        ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->inventoryCollection = $inventoryCollection;
        $this->locationCollection = $locationCollection;
        parent::__construct($context, $data);
        $this->catalogProductFactory = $catalogProductFactory;
        $this->configurable = $configurable;
        $this->resourceConnection = $resourceConnection;
    }

    public function getInventory()
    {
        $this->inventory = $this->inventoryCollection->create()
            ->addFieldToFilter('product_id', ['eq' => $this->getRequest()->getParam('id')])
            ->join(
                [
                    'locations' => $this->resourceConnection->getTableName('squareup_omni_location')
                ],
                'main_table.location_id = locations.square_id AND locations.status = 1'
            );

        return $this->inventory;
    }

    /**
     * Get locations
     *
     * @return array
     */
    public function getLocations()
    {
        $locationArr = [];
        $locations = $this->locationCollection->create()
            ->addFieldToFilter('status', 1);

        foreach ($locations as $location) {
            $locationArr[$location->getSquareId()] = $location->getName();
        }

        return $locationArr;
    }

    public function getNewLocations()
    {
        $inventoryArr = [];

        foreach ($this->inventory as $item) {
            $inventoryArr[] = $item->getLocationId();
        }

        $locations = $this->locationCollection->create()
            ->addFieldToFilter('status', ['eq' => 1]);

        if (!empty($inventoryArr)) {
            $locations->addFieldToFilter('square_id', ['nin' => $inventoryArr]);
        }

        return $locations;
    }

    /**
     * Check edit form
     *
     * @return bool
     */
    public function checkEditForm()
    {
        if ('edit' === $this->_request->getActionName()) {
            return true;
        }

        return false;
    }

    public function getProduct($productId)
    {
        return $this->catalogProductFactory->create()->load($productId);
    }

    public function getParentIds($productId)
    {
        $this->configurable->getParentIdsByChild($productId);
    }
}
