<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Firebear\ImportExport\Plugin\Model\Import;

use Firebear\ImportExport\Helper\Data;
use Firebear\ImportExport\Model\Source\Type\AbstractType;
use Magento\Catalog\Model\Product\Url;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Catalog\Model\ResourceModel\Product\LinkFactory;
use Magento\CatalogImportExport\Model\Import\Product as MagentoProduct;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\TaxClassProcessor;
use Magento\CatalogImportExport\Model\Import\Product\Type\Factory;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\Resource;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory;
use Magento\CatalogImportExport\Model\Import\UploaderFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\EntityFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Product
 */
class Product extends MagentoProduct
{
    /**
     * Default website id
     */
    const DEFAULT_WEBSITE_ID = 1;
    /**
     * Used when create new attributes in column name
     */
    const ATTRIBUTE_SET_GROUP = 'attribute_set_group';
    /**
     * Attribute sets column name
     */
    const ATTRIBUTE_SET_COLUMN = 'attribute_set';

    /**
     * @var Http
     */
    protected $_request;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var AbstractType
     */
    protected $_sourceType;

    /**
     * @var AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var EntityFactory
     */
    protected $eavEntityFactory;

    /**
     * @var CollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @var array
     */
    protected $_attributeSetGroupCache;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $productHelper;

    protected $output;//temp

    /**
     * @param Http                                                                    $request
     * @param Data                                                                    $helper
     * @param \Magento\Framework\Json\Helper\Data                                     $jsonHelper
     * @param \Magento\ImportExport\Helper\Data                                       $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data                   $importData
     * @param Config                                                                  $config
     * @param ResourceConnection                                                      $resource
     * @param Helper                                                                  $resourceHelper
     * @param StringUtils                                                             $string
     * @param ProcessingErrorAggregatorInterface                                      $errorAggregator
     * @param ManagerInterface                                                        $eventManager
     * @param StockRegistryInterface                                                  $stockRegistry
     * @param StockConfigurationInterface                                             $stockConfiguration
     * @param StockStateProviderInterface                                             $stockStateProvider
     * @param \Magento\Catalog\Helper\Data                                            $catalogData
     * @param Import\Config                                                           $importConfig
     * @param ResourceModelFactory                                                    $resourceFactory
     * @param MagentoProduct\OptionFactory                                            $optionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory
     * @param Factory                                                                 $productTypeFactory
     * @param LinkFactory                                                             $linkFactory
     * @param ProductFactory                                                          $proxyProdFactory
     * @param UploaderFactory                                                         $uploaderFactory
     * @param Filesystem                                                              $filesystem
     * @param ItemFactory                                                             $stockResItemFac
     * @param DateTime\TimezoneInterface                                              $localeDate
     * @param DateTime                                                                $dateTime
     * @param LoggerInterface                                                         $logger
     * @param IndexerRegistry                                                         $indexerRegistry
     * @param MagentoProduct\StoreResolver                                            $storeResolver
     * @param MagentoProduct\SkuProcessor                                             $skuProcessor
     * @param MagentoProduct\CategoryProcessor                                        $categoryProcessor
     * @param MagentoProduct\Validator                                                $validator
     * @param ObjectRelationProcessor                                                 $objectRelationProcessor
     * @param TransactionManagerInterface                                             $transactionManager
     * @param TaxClassProcessor                                                       $taxClassProcessor
     * @param ScopeConfigInterface                                                    $scopeConfig
     * @param Url                                                                     $productUrl
     * @param AttributeFactory                                                        $attributeFactory
     * @param array                                                                   $data
     */
    public function __construct(
        Http $request,
        Data $helper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        Config $config,
        ResourceConnection $resource,
        Helper $resourceHelper,
        StringUtils $string,
        \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface $errorAggregator,
        ManagerInterface $eventManager,
        StockRegistryInterface $stockRegistry,
        StockConfigurationInterface $stockConfiguration,
        StockStateProviderInterface $stockStateProvider,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\ImportExport\Model\Import\Config $importConfig,
        ResourceModelFactory $resourceFactory,
        \Magento\CatalogImportExport\Model\Import\Product\OptionFactory $optionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory,
        Factory $productTypeFactory,
        LinkFactory $linkFactory,
        ProductFactory $proxyProdFactory,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        ItemFactory $stockResItemFac,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        DateTime $dateTime,
        LoggerInterface $logger,
        IndexerRegistry $indexerRegistry,
        \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver,
        \Magento\CatalogImportExport\Model\Import\Product\SkuProcessor $skuProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor $categoryProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\Validator $validator,
        ObjectRelationProcessor $objectRelationProcessor,
        TransactionManagerInterface $transactionManager,
        TaxClassProcessor $taxClassProcessor,
        ScopeConfigInterface $scopeConfig,
        Url $productUrl,
        AttributeFactory $attributeFactory,
        EntityFactory $eavEntityFactory,
        CollectionFactory $groupCollectionFactory,
        \Magento\Catalog\Helper\Product $productHelper,
        array $data = []
    ) {
        $this->_request = $request;
        $this->_helper = $helper;
        $this->attributeFactory = $attributeFactory;
        $this->eavEntityFactory = $eavEntityFactory;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->productHelper = $productHelper;
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator,
            $eventManager,
            $stockRegistry,
            $stockConfiguration,
            $stockStateProvider,
            $catalogData,
            $importConfig,
            $resourceFactory,
            $optionFactory,
            $setColFactory,
            $productTypeFactory,
            $linkFactory,
            $proxyProdFactory,
            $uploaderFactory,
            $filesystem,
            $stockResItemFac,
            $localeDate,
            $dateTime,
            $logger,
            $indexerRegistry,
            $storeResolver,
            $skuProcessor,
            $categoryProcessor,
            $validator,
            $objectRelationProcessor,
            $transactionManager,
            $taxClassProcessor,
            $scopeConfig,
            $productUrl,
            $data
        );
    }

    /**
     * Initialize source type model
     *
     * @param $type
     *
     * @throws LocalizedException
     */
    protected function _initSourceType($type)
    {
        if(!$this->_sourceType) {
            $this->_sourceType = $this->_helper->getSourceModelByType($type);
            $this->_sourceType->setData($this->_parameters);
        }
    }

    /**
     * Gather and save information about product entities.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _saveProducts()
    {
        /** @var $resource Resource */
        if(isset($this->_parameters['import_source']) && $this->_parameters['import_source'] != 'file') {
            $this->_initSourceType($this->_parameters['import_source']);
        }
        $priceIsGlobal = $this->_catalogData->isPriceGlobal();
        $productLimit = null;
        $productsQty = null;
        while($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entityRowsIn = [];
            $entityRowsUp = [];
            $attributes = [];
            $this->websitesCache = [];
            $this->categoriesCache = [];
            $tierPrices = [];
            $mediaGallery = [];
            $uploadedImages = [];
            $previousType = null;
            $prevAttributeSet = null;
            if($this->_sourceType) {
                $bunch = $this->_prepareImagesFromSource($bunch);
            }
            foreach($bunch as $rowNum => $rowData) {
                if(!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                if($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                $rowScope = $this->getRowScope($rowData);
                $rowSku = $rowData[self::COL_SKU];
                if(null === $rowSku) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    // skip rows when SKU is NULL
                    continue;
                } else if(self::SCOPE_STORE == $rowScope) {
                    // set necessary data from SCOPE_DEFAULT row
                    $rowData[self::COL_TYPE] = $this->skuProcessor->getNewSku($rowSku)['type_id'];
                    $rowData['attribute_set_id'] = $this->skuProcessor->getNewSku($rowSku)['attr_set_id'];
                    $rowData[self::COL_ATTR_SET] = $this->skuProcessor->getNewSku($rowSku)['attr_set_code'];
                }
                // 1. Entity phase
                if(isset($this->_oldSku[$rowSku])) {
                    // existing row
                    $entityRowsUp[] = [
                        'updated_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                        'entity_id'  => $this->_oldSku[$rowSku]['entity_id'],
                    ];
                } else {
                    if(!$productLimit || $productsQty < $productLimit) {
                        $entityRowsIn[$rowSku] = [
                            'attribute_set_id' => $this->skuProcessor->getNewSku($rowSku)['attr_set_id'],
                            'type_id'          => $this->skuProcessor->getNewSku($rowSku)['type_id'],
                            'sku'              => $rowSku,
                            'has_options'      => isset($rowData['has_options']) ? $rowData['has_options'] : 0,
                            'created_at'       => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                            'updated_at'       => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                        ];
                        $productsQty++;
                    } else {
                        $rowSku = null;
                        // sign for child rows to be skipped
                        $this->getErrorAggregator()->addRowToSkip($rowNum);
                        continue;
                    }
                }
                if(!array_key_exists($rowSku, $this->websitesCache)) {
                    $this->websitesCache[$rowSku] = [];
                }
                // 2. Product-to-Website phase
                if(!empty($rowData[self::COL_PRODUCT_WEBSITES])) {
                    $websiteCodes = explode($this->getMultipleValueSeparator(), $rowData[self::COL_PRODUCT_WEBSITES]);
                    foreach($websiteCodes as $websiteCode) {
                        $websiteId = $this->storeResolver->getWebsiteCodeToId($websiteCode);
                        $this->websitesCache[$rowSku][$websiteId] = true;
                    }
                }
                // 3. Categories phase
                if(!array_key_exists($rowSku, $this->categoriesCache)) {
                    $this->categoriesCache[$rowSku] = [];
                }
                $rowData['rowNum'] = $rowNum;
                $categoryIds = $this->processRowCategories($rowData);
                foreach($categoryIds as $id) {
                    $this->categoriesCache[$rowSku][$id] = true;
                }
                unset($rowData['rowNum']);
                // 4.1. Tier prices phase
                if(!empty($rowData['_tier_price_website'])) {
                    $tierPrices[$rowSku][] = [
                        'all_groups'        => $rowData['_tier_price_customer_group'] == self::VALUE_ALL,
                        'customer_group_id' => $rowData['_tier_price_customer_group'] ==
                                               self::VALUE_ALL ? 0 : $rowData['_tier_price_customer_group'],
                        'qty'               => $rowData['_tier_price_qty'],
                        'value'             => $rowData['_tier_price_price'],
                        'website_id'        => self::VALUE_ALL == $rowData['_tier_price_website'] ||
                                               $priceIsGlobal ? 0 :
                            $this->storeResolver->getWebsiteCodeToId($rowData['_tier_price_website']),
                    ];
                }
                if(!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                // 5. Media gallery phase
                $disabledImages = [];
                list($rowImages, $rowLabels) = $this->getImagesFromRow($rowData);
                if(isset($rowData['_media_is_disabled'])) {
                    $disabledImages = array_flip(
                        explode($this->getMultipleValueSeparator(), $rowData['_media_is_disabled'])
                    );
                }
                $rowData[self::COL_MEDIA_IMAGE] = [];
                foreach($rowImages as $column => $columnImages) {
                    foreach($columnImages as $position => $columnImage) {
                        if(!isset($uploadedImages[$columnImage])) {
                            $uploadedFile = $this->uploadMediaFiles(trim($columnImage), true);
                            if($uploadedFile) {
                                $uploadedImages[$columnImage] = $uploadedFile;
                            } else {
                                $this->addRowError(
                                    ValidatorInterface::ERROR_MEDIA_URL_NOT_ACCESSIBLE,
                                    $rowNum,
                                    null,
                                    null,
                                    ProcessingError::ERROR_LEVEL_NOT_CRITICAL
                                );
                            }
                        } else {
                            $uploadedFile = $uploadedImages[$columnImage];
                        }
                        if($uploadedFile && $column !== self::COL_MEDIA_IMAGE) {
                            $rowData[$column] = $uploadedFile;
                        }
                        $imageNotAssigned = !isset($existingImages[$rowSku][$uploadedFile]);
                        if($uploadedFile && $imageNotAssigned) {
                            if($column == self::COL_MEDIA_IMAGE) {
                                $rowData[$column][] = $uploadedFile;
                            }
                            $mediaGallery[$rowSku][] = [
                                'attribute_id' => $this->getMediaGalleryAttributeId(),
                                'label'        => isset($rowLabels[$column][$position]) ?
                                    $rowLabels[$column][$position] : '',
                                'position'     => $position + 1,
                                'disabled'     => isset($disabledImages[$columnImage]) ? '1' : '0',
                                'value'        => $uploadedFile,
                            ];
                            $existingImages[$rowSku][$uploadedFile] = true;
                        }
                    }
                }
                // 6. Attributes phase
                $rowStore = (self::SCOPE_STORE == $rowScope)
                    ? $this->storeResolver->getStoreCodeToId($rowData[self::COL_STORE])
                    : 0;
                $productType = isset($rowData[self::COL_TYPE]) ? $rowData[self::COL_TYPE] : null;
                if($productType !== null) {
                    $previousType = $productType;
                }
                if(isset($rowData[self::COL_ATTR_SET])) {
                    $prevAttributeSet = $rowData[self::COL_ATTR_SET];
                }
                if(self::SCOPE_NULL == $rowScope) {
                    // for multiselect attributes only
                    if($prevAttributeSet !== null) {
                        $rowData[self::COL_ATTR_SET] = $prevAttributeSet;
                    }
                    if($productType === null && $previousType !== null) {
                        $productType = $previousType;
                    }
                    if($productType === null) {
                        continue;
                    }
                }
                $productTypeModel = $this->_productTypeModels[$productType];
                if(!empty($rowData['tax_class_name'])) {
                    $rowData['tax_class_id'] =
                        $this->taxClassProcessor->upsertTaxClass($rowData['tax_class_name'], $productTypeModel);
                }
                if($this->getBehavior() == Import::BEHAVIOR_APPEND ||
                   empty($rowData[self::COL_SKU])
                ) {
                    $rowData = $productTypeModel->clearEmptyData($rowData);
                }
                $rowData = $productTypeModel->prepareAttributesWithDefaultValueForSave(
                    $rowData,
                    !isset($this->_oldSku[$rowSku])
                );
                $product = $this->_proxyProdFactory->create(['data' => $rowData]);
                foreach($rowData as $attrCode => $attrValue) {
                    $attribute = $this->retrieveAttributeByCode($attrCode);
                    if('multiselect' != $attribute->getFrontendInput() && self::SCOPE_NULL == $rowScope) {
                        // skip attribute processing for SCOPE_NULL rows
                        continue;
                    }
                    $attrId = $attribute->getId();
                    $backModel = $attribute->getBackendModel();
                    $attrTable = $attribute->getBackend()->getTable();
                    $storeIds = [0];
                    if('datetime' == $attribute->getBackendType() && strtotime($attrValue)) {
                        $attrValue = $this->dateTime->gmDate(
                            'Y-m-d H:i:s',
                            $this->_localeDate->date($attrValue)->getTimestamp()
                        );
                    } else if($backModel) {
                        $attribute->getBackend()->beforeSave($product);
                        $attrValue = $product->getData($attribute->getAttributeCode());
                    }
                    if(self::SCOPE_STORE == $rowScope) {
                        if(self::SCOPE_WEBSITE == $attribute->getIsGlobal()) {
                            // check website defaults already set
                            if(!isset($attributes[$attrTable][$rowSku][$attrId][$rowStore])) {
                                $storeIds = $this->storeResolver->getStoreIdToWebsiteStoreIds($rowStore);
                            }
                        } else if(self::SCOPE_STORE == $attribute->getIsGlobal()) {
                            $storeIds = [$rowStore];
                        }
                        if(!isset($this->_oldSku[$rowSku])) {
                            $storeIds[] = 0;
                        }
                    }
                    foreach($storeIds as $storeId) {
                        if(!isset($attributes[$attrTable][$rowSku][$attrId][$storeId])) {
                            $attributes[$attrTable][$rowSku][$attrId][$storeId] = $attrValue;
                        }
                    }
                    // restore 'backend_model' to avoid 'default' setting
                    $attribute->setBackendModel($backModel);
                }
            }
            if(method_exists($this, '_saveProductEntity')) {
                $this->_saveProductEntity(
                    $entityRowsIn,
                    $entityRowsUp
                );
            } else {
                $this->saveProductEntity(
                    $entityRowsIn,
                    $entityRowsUp
                );
            }
            $this->_saveProductWebsites(
                $this->websitesCache
            )->_saveProductCategories(
                $this->categoriesCache
            )->_saveProductTierPrices(
                $tierPrices
            )->_saveMediaGallery(
                $mediaGallery
            )->_saveProductAttributes(
                $attributes
            );
            $this->_eventManager->dispatch(
                'catalog_product_import_bunch_save_after',
                ['adapter' => $this, 'bunch' => $bunch]
            );
        }

        return $this;
    }

    /**
     * Stock item saving.
     *
     * @return $this
     */
    protected function _saveStockItem()
    {
        $indexer = $this->indexerRegistry->get('catalog_product_category');
        /** @var $stockResource \Magento\CatalogInventory\Model\ResourceModel\Stock\Item */
        $stockResource = $this->_stockResItemFac->create();
        $entityTable = $stockResource->getMainTable();
        while($bunch = $this->_dataSourceModel->getNextBunch()) {
            $stockData = [];
            $productIdsToReindex = [];
            // Format bunch to stock data rows
            foreach($bunch as $rowNum => $rowData) {
                if(!$this->isRowAllowedToImport($rowData, $rowNum)) {
                    continue;
                }
                $row = [];
                $row['product_id'] = $this->skuProcessor->getNewSku($rowData[self::COL_SKU])['entity_id'];
                $productIdsToReindex[] = $row['product_id'];
                $row['website_id'] = $this->stockConfiguration->getDefaultScopeId();
                $row['stock_id'] = $this->stockRegistry->getStock($row['website_id'])->getStockId();
                $stockItemDo = $this->stockRegistry->getStockItem($row['product_id'], $row['website_id']);
                $existStockData = $stockItemDo->getData();
                $row = array_merge(
                    $this->defaultStockData,
                    array_intersect_key($existStockData, $this->defaultStockData),
                    array_intersect_key($rowData, $this->defaultStockData),
                    $row
                );
                if($this->stockConfiguration->isQty(
                    $this->skuProcessor->getNewSku($rowData[self::COL_SKU])['type_id']
                )
                ) {
                    $stockItemDo->setData($row);
                    $row['is_in_stock'] = $this->stockStateProvider->verifyStock($stockItemDo);
                    if($this->stockStateProvider->verifyNotification($stockItemDo)) {
                        $row['low_stock_date'] = $this->dateTime->gmDate(
                            'Y-m-d H:i:s',
                            (new \DateTime())->getTimestamp()
                        );
                    }
                    $row['stock_status_changed_auto'] =
                        (int) !$this->stockStateProvider->verifyStock($stockItemDo);
                } else {
                    $row['qty'] = 0;
                }
                if(!isset($stockData[$rowData[self::COL_SKU]])) {
                    $stockData[$rowData[self::COL_SKU]] = $row;
                }
            }
            // Insert rows
            if(!empty($stockData)) {
                $this->_connection->insertOnDuplicate($entityTable, array_values($stockData));
            }
            if($productIdsToReindex) {
                $indexer->reindexList($productIdsToReindex);
            }
        }

        return $this;
    }

    /**
     * Import images via initialized source type
     *
     * @param $bunch
     *
     * @return mixed
     */
    protected function _prepareImagesFromSource($bunch)
    {
        foreach($bunch as &$rowData) {
            $rowData = $this->_customFieldsMapping($rowData);
            foreach($this->_imagesArrayKeys as $image) {
                if(empty($rowData[$image])) {
                    continue;
                }
                $dispersionPath =
                    \Magento\Framework\File\Uploader::getDispretionPath($rowData[$image]);
                $importImages = explode($this->getMultipleValueSeparator(), $rowData[$image]);
                foreach($importImages as $importImage) {
                    $imageSting = mb_strtolower(
                        $dispersionPath . '/' . preg_replace('/[^a-z0-9\._-]+/i', '', $importImage)
                    );
                    if($this->_sourceType) {
                        $this->_sourceType->importImage($importImage, $imageSting);
                    }
                    $rowData[$image] = $this->_sourceType->getCode() . $imageSting;
                }
            }
        }

        return $bunch;
    }

    /**
     * Retrieving images from all columns and rows
     *
     * @param $bunch
     *
     * @return array
     */
    protected function getBunchImages($bunch)
    {
        $allImagesFromBunch = [];
        foreach($bunch as $rowData) {
            $rowData = $this->_customFieldsMapping($rowData);
            foreach($this->_imagesArrayKeys as $image) {
                if(empty($rowData[$image])) {
                    continue;
                }
                $dispersionPath =
                    \Magento\Framework\File\Uploader::getDispretionPath($rowData[$image]);
                $importImages = explode($this->getMultipleValueSeparator(), $rowData[$image]);
                foreach($importImages as $importImage) {
                    $imageSting = mb_strtolower(
                        $dispersionPath . '/' . preg_replace('/[^a-z0-9\._-]+/i', '', $importImage)
                    );
                    if(isset($this->_parameters['import_source']) && $this->_parameters['import_source'] != 'file') {
                        $allImagesFromBunch[$this->_sourceType->getCode() . $imageSting] = $imageSting;
                    } else {
                        $allImagesFromBunch[$importImage] = $imageSting;
                    }
                }
            }
        }

        return $allImagesFromBunch;
    }

    /**
     * Custom fields mapping for changed purposes of fields and field names.
     *
     * @param array $rowData
     *
     * @return array
     */
    private function _customFieldsMapping($rowData)
    {
        foreach($this->_fieldsMap as $systemFieldName => $fileFieldName) {
            if(array_key_exists($fileFieldName, $rowData)) {
                $rowData[$systemFieldName] = $rowData[$fileFieldName];
            }
        }
        $rowData = $this->_parseAdditionalAttributes($rowData);
        $rowData = $this->_setStockUseConfigFieldsValues($rowData);
        if(array_key_exists('status', $rowData)
           && $rowData['status'] != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
        ) {
            if($rowData['status'] == 'yes') {
                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
            } else if(!empty($rowData['status']) || $this->getRowScope($rowData) == self::SCOPE_DEFAULT) {
                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
            }
        }

        return $rowData;
    }

    /**
     * Parse attributes names and values string to array.
     *
     * @param array $rowData
     *
     * @return array
     */
    private function _parseAdditionalAttributes($rowData)
    {
        if(empty($rowData['additional_attributes'])) {
            return $rowData;
        }
        $valuePairs = explode($this->getMultipleValueSeparator(), $rowData['additional_attributes']);
        foreach($valuePairs as $valuePair) {
            $separatorPosition = strpos($valuePair, self::PAIR_NAME_VALUE_SEPARATOR);
            if($separatorPosition !== false) {
                $key = substr($valuePair, 0, $separatorPosition);
                $value = substr(
                    $valuePair,
                    $separatorPosition + strlen(self::PAIR_NAME_VALUE_SEPARATOR)
                );
                $rowData[$key] = $value === false ? '' : $value;
            }
        }

        return $rowData;
    }

    /**
     * Set values in use_config_ fields.
     *
     * @param array $rowData
     *
     * @return array
     */
    private function _setStockUseConfigFieldsValues($rowData)
    {
        $useConfigFields = [];
        foreach($rowData as $key => $value) {
            if(
                isset($this->defaultStockData[$key])
                && isset($this->defaultStockData[self::INVENTORY_USE_CONFIG_PREFIX . $key])
                && !empty($value)
            ) {
                $fullKey = self::INVENTORY_USE_CONFIG_PREFIX . $key;
                $useConfigFields[$fullKey] = ($value == self::INVENTORY_USE_CONFIG) ? 1 : 0;
            }
        }
        $rowData = array_merge($rowData, $useConfigFields);

        return $rowData;
    }
}
