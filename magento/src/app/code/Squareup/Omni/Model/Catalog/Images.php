<?php
/**
 * Created by PhpStorm.
 * User: Ion Bogatu
 * Date: 5/29/2018
 * Time: 11:19 AM
 */

namespace Squareup\Omni\Model\Catalog;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Squareup\Omni\Helper\Config;
use Squareup\Omni\Helper\Data;
use Squareup\Omni\Helper\Mapping;
use Squareup\Omni\Logger\Logger;
use Squareup\Omni\Model\Square;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Catalog\Model\ProductFactory as CatalogProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as CatalogProductCollectionFactory;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Zend_Db_Select;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Catalog\Model\ResourceModel\Product;

class Images extends Square
{
    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var CatalogProductFactory
     */
    private $catalogProductFactory;
    /**
     * @var Iterator
     */
    private $iterator;
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var array
     */
    private $products = [];
    /**
     * @var CatalogProductCollectionFactory
     */
    private $catalogProductCollectionFactory;
    /**
     * @var MessageManager
     */
    private $messageManager;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    private $column;

    private $productResource;

    /**
     * Images constructor.
     * @param Config $config
     * @param Logger $logger
     * @param Data $helper
     * @param Mapping $mapping
     * @param Context $context
     * @param Registry $registry
     * @param DateTime $dateTime
     * @param CatalogProductFactory $catalogProductFactory
     * @param CatalogProductCollectionFactory $catalogProductCollectionFactory
     * @param Iterator $iterator
     * @param DirectoryList $directoryList
     * @param MessageManager $messageManager
     * @param ProductRepositoryInterface $productRepository
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Config $config,
        Logger $logger,
        Data $helper,
        Mapping $mapping,
        Context $context,
        Registry $registry,
        DateTime $dateTime,
        CatalogProductFactory $catalogProductFactory,
        CatalogProductCollectionFactory $catalogProductCollectionFactory,
        Iterator $iterator,
        DirectoryList $directoryList,
        MessageManager $messageManager,
        ProductRepositoryInterface $productRepository,
        ProductMetadataInterface $productMetadata,
        Product $productResource,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $config,
            $logger,
            $helper,
            $mapping,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->dateTime = $dateTime;
        $this->catalogProductFactory = $catalogProductFactory;
        $this->iterator = $iterator;
        $this->directoryList = $directoryList;
        $this->catalogProductCollectionFactory = $catalogProductCollectionFactory;
        $this->messageManager = $messageManager;
        $this->productRepository = $productRepository;
        $this->productResource = $productResource;
        $this->column = 'entity_id';

        if ('Enterprise' == $productMetadata->getEdition()) {
            $this->column = 'row_id';
        }
    }

    /**
     * @param array $products
     */
    public function setProducts($products = [])
    {
        $this->products = $products;
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function start()
    {
        if($this->configHelper->getSor() == \Squareup\Omni\Model\System\Config\Source\Options\Records::SQUARE){
            $this->imagesImport();
            $this->configHelper->saveImagesRanAt($this->dateTime->gmtDate('Y-m-d H:i:s'));
        } else {
            $this->imagesExport();
            $this->configHelper->saveImagesRanAt($this->dateTime->gmtDate('Y-m-d H:i:s'));
        }

        return true;
    }

    /**
     * Images export
     */
    public function imagesExport()
    {
        $this->processProducts();
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function imagesImport(){
        $client = $this->helper->getClientApi();
        $api = new \SquareConnect\Api\CatalogApi($client);
        $cursor = null;

        $s = 1;
        while ($s != 0) {
            try {
                $apiResponse = $api->listCatalog($cursor, 'ITEM');
            } catch (\SquareConnect\ApiException $e) {
                $errors = $e->getResponseBody()->errors;
                $this->logger->error($e->__toString());

                $errorDetail = '';
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        $errorDetail = $error->category;
                    }

                    $this->messageManager->addErrorMessage(
                        __(
                            '%s Make sure you retrieved OAuth Token or you selected a location.',
                            $errorDetail
                        )
                    );
                }

                return false;
            }

            $cursor = $apiResponse->getCursor();

            if (null !== $apiResponse->getErrors()) {
                $this->logger->error('There was an error in the response from Square, to catalog items');
                return false;
            }

            if (null === $apiResponse->getObjects()) {
                $this->logger->error('There are no items square to import');
                return false;
            }

            $imagesCronRanAt = strtotime($this->configHelper->getImagesRanAt());
            if(empty($this->configHelper->getImagesRanAt())){
                $imagesCronRanAt = 0;
            }
            foreach ($apiResponse->getObjects() as $item) {
                $itemUpdatedAt = strtotime($item->getUpdatedAt());
                if($itemUpdatedAt > $imagesCronRanAt ) {
                    /**
                     * @var \Magento\Catalog\Model\Product $product
                     */
                    $product = $this->catalogProductFactory->create()
                        ->loadByAttribute('square_id', $item->getId());
                    if(!$product){
                        continue;
                    }
                    if ($item->getType() == 'ITEM' && null !== $item->getItemData()
                            ->getImageUrl()) {
                        $imageName = $this->downloadImage($item->getItemData()
                            ->getImageUrl(), $item->getId());
                        if (false !== $imageName) {
                            $product->setMediaGalleryEntries([]);
                            $this->productRepository->save($product);
                            $product->addImageToMediaGallery(
                                $imageName,
                                ['image', 'small_image', 'thumbnail'],
                                true,
                                false
                            );
                            $product->save();
                        }
                    }
                }
            }

            if ($cursor === null) {
                $s = 0;
            }
        }
    }

    /**
     * @param $image
     * @param $id
     * @return bool|string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function downloadImage($image, $id){
        $config = [
            'adapter'   => 'Zend_Http_Client_Adapter_Curl',
            'curloptions' => [CURLOPT_SSL_VERIFYPEER => false],
        ];

        try {
            $client = new \Zend_Http_Client($image, $config);
            $client->setMethod(\Zend_Http_Client::GET);
            $client->setConfig(['timeout' => 60]);
            $response = $client->request();
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        if ($response->getStatus() != 200) {
            $this->logger->error('There was an error when trying to download image: ' . $image);
            return false;
        }

        $fileArr = explode('/', $image);
        $fileName = array_pop($fileArr);
        $extArr = explode('.', $fileName);
        $ext = array_pop($extArr);
        $fullPath = $this->directoryList->getPath('media') . '/square/' . $id . '.' . $ext;
        $file = new \SplFileObject($fullPath, 'w+');
        $file->fwrite($response->getBody());
        $file = null;

        return $fullPath;
    }

    /**
     * Process products
     */
    public function processProducts()
    {
        $ranAt = $this->configHelper->getImagesRanAt();
        if (null === $ranAt) {
            $ranAt = 1;
        }

        $fromDate = $this->dateTime->gmtDate('Y-m-d H:i:s', $ranAt);
        $toDate = $this->dateTime->gmtDate('Y-m-d H:i:s');

        $collection = $this->catalogProductFactory->create()
            ->getCollection();

        if ($this->products) {
            $collection->addFieldToFilter($this->column, ['in' => $this->products]);
        } else {
            $collection->addAttributeToFilter('updated_at', ['from' => $fromDate, 'to' => $toDate])
                ->addAttributeToFilter('square_id', ['notnull' => true]);
        }

        $collection->setStoreId(0);
        $collection->addAttributeToSelect(
            [
                'name',
                'square_id',
                'sku',
                'image',
                'square_variation_id'
            ],
            'left'
        );

        $this->iterator->walk(
            $collection->getSelect(),
            [[$this, 'processProduct']],
            ['ranAt' => $ranAt]
        );
    }

    /**
     * Process product
     *
     * @param $args
     * @return array
     */
    public function processProduct($args)
    {
        try {
            if ($args['row']['square_id'] == $args['row']['square_variation_id']) {
                $this->configHelper->saveImagesRanAt($args['row']['updated_at']);
                return [];
            }

            $model = $this->catalogProductFactory->create()
                ->getResource();

            $image = $model->getAttributeRawValue($args['row'][$this->column], 'image', 0);
            if (false === $image || empty($image)) {
                $this->configHelper->saveImagesRanAt($args['row']['updated_at']);
                return [];
            }

            if ('no_selection' === $image) {
                return [];
            }

            $fullImage = $this->directoryList->getPath(DirectoryList::MEDIA) . '/catalog/product' . $image;
            $fileInfo = new \SplFileInfo($fullImage);
            if ($args['ranAt'] < $fileInfo->getMTime()) {
                $this->callSquare($args['row'][$this->column], $args['row']['square_id'], $fullImage);
            }
        } catch (\Exception $e) {
            $this->logger->error('Info:  square_Id: ' .  $args['row']['square_id']);
            $this->logger->error($e->getMessage());
            $this->logger->error($e->__toString());
        }

        $this->configHelper->saveImagesRanAt($args['row']['updated_at']);
    }

    /**
     * Call square
     *
     * @param $itemId
     * @param $image
     *
     * @return bool
     */
    public function callSquare($productId, $itemId, $image)
    {
        $locationId = $this->configHelper->getLocationId();
        $authToken = $this->configHelper->getOAuthToken();
        $url = 'https://connect.squareup.com/v1/' . $locationId . '/items/' . $itemId . '/image';
        $config = [
            'adapter' => 'Zend_Http_Client_Adapter_Socket',
        ];
        $oauthRequestHeaders = [
            'Authorization' => 'Bearer ' . $authToken,
            'Accept' => 'application/json',
            'Content-Type' => 'multipart/form-data'
        ];

        try {
            $client = new \Zend_Http_Client($url, $config);
            $client->setMethod(\Zend_Http_Client::POST);
            $client->setHeaders($oauthRequestHeaders);
            $client->setConfig(['timeout' => 60]);
            $client->setFileUpload($image, 'image_data');
            $response = $client->request();

            if ($response->getStatus() == 200) {
                $body = json_decode($response->getBody());
                $product = $this->catalogProductFactory->create();
                $product->setId($productId);
                $product->setRowId($productId);
                $url = explode('=', $body->url);
                $product->setSquareProductImage(ltrim($url[1], '/'));
                $this->productResource->saveAttribute($product, 'square_product_image');
            }
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());
            return false;
        }

        if ($response->getStatus() != 200) {
            $this->logger->error($response->__toString());
            return false;
        }

        return true;
    }

    public function getCollectionSize()
    {
        $collection = $this->getRequiredCollection();

        return $collection->getSize();
    }

    public function getRequiredCollection()
    {
        $ranAt = $this->configHelper->getImagesRanAt();
        if (null === $ranAt) {
            $ranAt = $this->dateTime->gmtDate('Y-m-d H:i:s', $ranAt);
        }

        $toDate = $this->dateTime->gmtDate('Y-m-d H:i:s');

        $collection = $this->catalogProductCollectionFactory->create()
            ->addAttributeToSelect(
                [
                    'name',
                    'square_id',
                    'sku',
                    'image',
                    'square_variation_id'
                ],
                'left'
            )
            ->addAttributeToFilter('updated_at', ['from' => $ranAt, 'to' => $toDate])
            ->addAttributeToFilter('square_id', ['from' => true]);
        $collection->getSelect()
            ->reset(Zend_Db_Select::ORDER);
        $collection->getSelect()
            ->order('e.updated_at asc');

        return $collection;
    }
}
