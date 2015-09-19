<?php
namespace Firebear\ImportExport\Plugin\Model\Import;

use Magento\Framework\Stdlib\DateTime;
use Magento\CatalogImportExport\Model\Import\Product as MagentoProduct;

class Product extends \Magento\CatalogImportExport\Model\Import\Product {

    protected $_request;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\Resource\Import\Data $importData,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\Resource $resource,
        \Magento\ImportExport\Model\Resource\Helper $resourceHelper,
        \Magento\Framework\Stdlib\String $string,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\ImportExport\Model\Import\Config $importConfig,
        \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceFactory $resourceFactory,
        \Magento\CatalogImportExport\Model\Import\Product\OptionFactory $optionFactory,
        \Magento\Eav\Model\Resource\Entity\Attribute\Set\CollectionFactory $setColFactory,
        \Magento\CatalogImportExport\Model\Import\Product\Type\Factory $productTypeFactory,
        \Magento\Catalog\Model\Resource\Product\LinkFactory $linkFactory,
        \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory,
        \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\CatalogInventory\Model\Resource\Stock\ItemFactory $stockResItemFac,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        DateTime $dateTime,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Indexer\Model\IndexerRegistry $indexerRegistry,
        MagentoProduct\StoreResolver $storeResolver,
        MagentoProduct\SkuProcessor $skuProcessor,
        MagentoProduct\CategoryProcessor $categoryProcessor,
        MagentoProduct\Validator $validator,
        \Magento\Framework\Model\Resource\Db\ObjectRelationProcessor $objectRelationProcessor,
        \Magento\Framework\Model\Resource\Db\TransactionManagerInterface $transactionManager,
        MagentoProduct\TaxClassProcessor $taxClassProcessor,
        array $data = []
    ){
        $this->_request = $request;

        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
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
            $data
        );
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
        /** @var $resource \Magento\CatalogImportExport\Model\Import\Proxy\Product\Resource */
        $resource = $this->_resourceFactory->create();
        $priceIsGlobal = $this->_catalogData->isPriceGlobal();
        $productLimit = null;
        $productsQty = null;

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entityRowsIn = [];
            $entityRowsUp = [];
            $attributes = [];
            $this->websitesCache = [];
            $this->categoriesCache = [];
            $tierPrices = [];
            $groupPrices = [];
            $mediaGallery = [];
            $uploadedGalleryFiles = [];
            $previousType = null;
            $prevAttributeSet = null;

            if(isset($this->_parameters['import_source']) && $this->_parameters['import_source'] == 'dropbox') {
                $bunch = $this->_prepareDropboxImages($bunch);
            }
            $allImagesFromBunch = $this->_getAllBunchImages($bunch);
            $existingImages = $this->_prepareAllMediaFiles($allImagesFromBunch);

            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                $rowScope = $this->getRowScope($rowData);

                $rowSku = $rowData[self::COL_SKU];

                if (null === $rowSku) {
                    $this->_rowsToSkip[$rowNum] = true;
                    // skip rows when SKU is NULL
                    continue;
                } elseif (self::SCOPE_STORE == $rowScope) {
                    // set necessary data from SCOPE_DEFAULT row
                    $rowData[self::COL_TYPE] = $this->skuProcessor->getNewSku($rowSku)['type_id'];
                    $rowData['attribute_set_id'] = $this->skuProcessor->getNewSku($rowSku)['attr_set_id'];
                    $rowData[self::COL_ATTR_SET] = $this->skuProcessor->getNewSku($rowSku)['attr_set_code'];
                }

                // 1. Entity phase
                if (isset($this->_oldSku[$rowSku])) {
                    // existing row
                    $entityRowsUp[] = [
                        'updated_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                        'entity_id' => $this->_oldSku[$rowSku]['entity_id'],
                    ];
                } else {
                    if (!$productLimit || $productsQty < $productLimit) {
                        $entityRowsIn[$rowSku] = [
                            'attribute_set_id' => $this->skuProcessor->getNewSku($rowSku)['attr_set_id'],
                            'type_id' => $this->skuProcessor->getNewSku($rowSku)['type_id'],
                            'sku' => $rowSku,
                            'has_options' => isset($rowData['has_options']) ? $rowData['has_options'] : 0,
                            'created_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                            'updated_at' => (new \DateTime())->format(DateTime::DATETIME_PHP_FORMAT),
                        ];
                        $productsQty++;
                    } else {
                        $rowSku = null;
                        // sign for child rows to be skipped
                        $this->_rowsToSkip[$rowNum] = true;
                        continue;
                    }
                }

                $this->websitesCache[$rowSku] = [];
                // 2. Product-to-Website phase
                if (!empty($rowData[self::COL_PRODUCT_WEBSITES])) {
                    $websiteCodes = explode($this->getMultipleValueSeparator(), $rowData[self::COL_PRODUCT_WEBSITES]);
                    foreach ($websiteCodes as $websiteCode) {
                        $websiteId = $this->storeResolver->getWebsiteCodeToId($websiteCode);
                        $this->websitesCache[$rowSku][$websiteId] = true;
                    }
                }

                // 3. Categories phase
                $categoriesString = empty($rowData[self::COL_CATEGORY]) ? '' : $rowData[self::COL_CATEGORY];
                $this->categoriesCache[$rowSku] = [];
                if (!empty($categoriesString)) {
                    foreach ($this->categoryProcessor->upsertCategories($categoriesString) as $categoryId) {
                        $this->categoriesCache[$rowSku][$categoryId] = true;
                    }
                }

                // 4.1. Tier prices phase
                if (!empty($rowData['_tier_price_website'])) {
                    $tierPrices[$rowSku][] = [
                        'all_groups' => $rowData['_tier_price_customer_group'] == self::VALUE_ALL,
                        'customer_group_id' => $rowData['_tier_price_customer_group'] ==
                        self::VALUE_ALL ? 0 : $rowData['_tier_price_customer_group'],
                        'qty' => $rowData['_tier_price_qty'],
                        'value' => $rowData['_tier_price_price'],
                        'website_id' => self::VALUE_ALL == $rowData['_tier_price_website'] ||
                        $priceIsGlobal ? 0 : $this->storeResolver->getWebsiteCodeToId($rowData['_tier_price_website']),
                    ];
                }

                // 4.2. Group prices phase
                if (!empty($rowData['_group_price_website'])) {
                    $groupPrices[$rowSku][] = [
                        'all_groups' => $rowData['_group_price_customer_group'] == self::VALUE_ALL,
                        'customer_group_id' => $rowData['_group_price_customer_group'] ==
                        self::VALUE_ALL ? 0 : $rowData['_group_price_customer_group'],
                        'value' => $rowData['_group_price_price'],
                        'website_id' => self::VALUE_ALL == $rowData['_group_price_website'] ||
                        $priceIsGlobal ? 0 : $this->storeResolver->getWebsiteCodeToId($rowData['_group_price_website']),
                    ];
                }

                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }

                // 5. Media gallery phase
                $mediaGalleryImages = array();
                $mediaGalleryLabels = array();
                if (!empty($rowData[self::COL_MEDIA_IMAGE])) {
                    $mediaGalleryImages =
                        explode($this->getMultipleValueSeparator(), $rowData[self::COL_MEDIA_IMAGE]);
                    if (isset($rowData['_media_image_label'])) {
                        $mediaGalleryLabels =
                            explode($this->getMultipleValueSeparator(), $rowData['_media_image_label']);
                    } else {
                        $mediaGalleryLabels = [];
                    }
                    if (count($mediaGalleryLabels) > count($mediaGalleryImages)) {
                        $mediaGalleryLabels = array_slice($mediaGalleryLabels, 0, count($mediaGalleryImages));
                    } elseif (count($mediaGalleryLabels) < count($mediaGalleryImages)) {
                        $mediaGalleryLabels = array_pad($mediaGalleryLabels, count($mediaGalleryImages), '');
                    }
                }

                foreach ($this->_imagesArrayKeys as $imageCol) {
                    if (!empty($rowData[$imageCol])
                        && ($imageCol != self::COL_MEDIA_IMAGE)
                        && !in_array($rowData[$imageCol], $mediaGalleryImages)
                    ) {
                        $mediaGalleryImages[] = $rowData[$imageCol];
                        if (isset($mediaGalleryLabels)) {
                            $mediaGalleryLabels[] = isset($rowData[$imageCol . '_label'])
                                ? $rowData[$imageCol . '_label']
                                : '';
                        } else {
                            $mediaGalleryLabels[] = '';
                        }
                    }
                }
                $rowData[self::COL_MEDIA_IMAGE] = array();
                foreach ($mediaGalleryImages as $mediaImage) {
                    $imagePath = $allImagesFromBunch[$mediaImage];
                    if (isset($existingImages[$imagePath]) && in_array($rowSku, $existingImages[$imagePath])) {
                        if (!array_key_exists($mediaImage, $uploadedGalleryFiles)) {
                            $uploadedGalleryFiles[$mediaImage] = $this->_uploadMediaFiles(
                                trim($mediaImage),
                                true
                            );
                        }
                    } elseif (!isset($existingImages[$imagePath])) {
                        if (!array_key_exists($mediaImage, $uploadedGalleryFiles)) {
                            $uploadedGalleryFiles[$mediaImage] = $this->_uploadMediaFiles(
                                trim($mediaImage),
                                true
                            );
                            $newImagePath = $uploadedGalleryFiles[$mediaImage];
                            $existingImages[$newImagePath][] = $rowSku;
                        }
                        $rowData[self::COL_MEDIA_IMAGE][] = $uploadedGalleryFiles[$mediaImage];
                        if (!empty($rowData[self::COL_MEDIA_IMAGE]) && is_array($rowData[self::COL_MEDIA_IMAGE])) {
                            $position = array_search($mediaImage, $mediaGalleryImages);
                            foreach ($rowData[self::COL_MEDIA_IMAGE] as $mediaImage) {
                                $mediaGallery[$rowSku][] = [
                                    'attribute_id' => $this->getMediaGalleryAttributeId(),
                                    'label' => isset($mediaGalleryLabels[$position]) ? $mediaGalleryLabels[$position] : '',
                                    'position' => $position,
                                    'disabled' => '',
                                    'value' => $mediaImage,
                                ];
                            }
                        }
                    }
                    foreach ($this->_imagesArrayKeys as $imageCol) {
                        if (empty($rowData[$imageCol]) || ($imageCol == self::COL_MEDIA_IMAGE)) {
                            continue;
                        }
                        if (isset($existingImages[$imagePath])
                            && !in_array($rowSku, $existingImages[$imagePath])
                            && (($rowData[$imageCol] == $imagePath) || ($rowData[$imageCol] == $mediaImage))
                        ) {
                            unset($rowData[$imageCol]);
                        } elseif (isset($uploadedGalleryFiles[$rowData[$imageCol]])) {
                            $rowData[$imageCol] = $uploadedGalleryFiles[$rowData[$imageCol]];
                        }
                    }
                }

                // 6. Attributes phase
                $rowStore = (self::SCOPE_STORE == $rowScope)
                    ? $this->storeResolver->getStoreCodeToId($rowData[self::COL_STORE])
                    : 0;
                $productType = isset($rowData[self::COL_TYPE]) ? $rowData[self::COL_TYPE] : null;
                if (!is_null($productType)) {
                    $previousType = $productType;
                }
                if (isset($rowData[self::COL_ATTR_SET])) {
                    $prevAttributeSet = $rowData[self::COL_ATTR_SET];
                }
                if (self::SCOPE_NULL == $rowScope) {
                    // for multiselect attributes only
                    if (!is_null($prevAttributeSet)) {
                        $rowData[self::COL_ATTR_SET] = $prevAttributeSet;
                    }
                    if (is_null($productType) && !is_null($previousType)) {
                        $productType = $previousType;
                    }
                    if (is_null($productType)) {
                        continue;
                    }
                }

                $productTypeModel = $this->_productTypeModels[$productType];
                if (!empty($rowData['tax_class_name'])) {
                    $rowData['tax_class_id'] =
                        $this->taxClassProcessor->upsertTaxClass($rowData['tax_class_name'], $productTypeModel);
                }

                if ($this->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND ||
                    empty($rowData[self::COL_SKU])
                ) {
                    $rowData = $productTypeModel->clearEmptyData($rowData);
                }

                $rowData = $productTypeModel->prepareAttributesWithDefaultValueForSave(
                    $rowData,
                    !isset($this->_oldSku[$rowSku])
                );
                $product = $this->_proxyProdFactory->create(['data' => $rowData]);

                foreach ($rowData as $attrCode => $attrValue) {
                    if (!isset($this->_attributeCache[$attrCode])) {
                        $this->_attributeCache[$attrCode] = $resource->getAttribute($attrCode);
                    }
                    $attribute = $this->_attributeCache[$attrCode];

                    if ('multiselect' != $attribute->getFrontendInput() && self::SCOPE_NULL == $rowScope) {
                        // skip attribute processing for SCOPE_NULL rows
                        continue;
                    }
                    $attrId = $attribute->getId();
                    $backModel = $attribute->getBackendModel();
                    $attrTable = $attribute->getBackend()->getTable();
                    $storeIds = [0];

                    if ('datetime' == $attribute->getBackendType() && strtotime($attrValue)) {
                        $attrValue = (new \DateTime())->setTimestamp(strtotime($attrValue));
                        $attrValue = $attrValue->format(DateTime::DATETIME_PHP_FORMAT);
                    } elseif ($backModel) {
                        $attribute->getBackend()->beforeSave($product);
                        $attrValue = $product->getData($attribute->getAttributeCode());
                    }
                    if (self::SCOPE_STORE == $rowScope) {
                        if (self::SCOPE_WEBSITE == $attribute->getIsGlobal()) {
                            // check website defaults already set
                            if (!isset($attributes[$attrTable][$rowSku][$attrId][$rowStore])) {
                                $storeIds = $this->storeResolver->getStoreIdToWebsiteStoreIds($rowStore);
                            }
                        } elseif (self::SCOPE_STORE == $attribute->getIsGlobal()) {
                            $storeIds = [$rowStore];
                        }
                        if (!isset($this->_oldSku[$rowSku])) {
                            $storeIds[] = 0;
                        }
                    }
                    foreach ($storeIds as $storeId) {
                        if ('multiselect' == $attribute->getFrontendInput()) {
                            if (!isset($attributes[$attrTable][$rowSku][$attrId][$storeId])) {
                                $attributes[$attrTable][$rowSku][$attrId][$storeId] = '';
                            } else {
                                $attributes[$attrTable][$rowSku][$attrId][$storeId] .= ',';
                            }
                            $attributes[$attrTable][$rowSku][$attrId][$storeId] .= $attrValue;
                        } else {
                            $attributes[$attrTable][$rowSku][$attrId][$storeId] = $attrValue;
                        }
                    }
                    // restore 'backend_model' to avoid 'default' setting
                    $attribute->setBackendModel($backModel);
                }
            }

            $this->_saveProductEntity(
                $entityRowsIn,
                $entityRowsUp
            )->_saveProductWebsites(
                $this->websitesCache
            )->_saveProductCategories(
                $this->categoriesCache
            )->_saveProductTierPrices(
                $tierPrices
            )->_saveProductGroupPrices(
                $groupPrices
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

    protected function _prepareDropboxImages($bunch)
    {
        $dbxClient = new \Dropbox\Client('yjjwzEgxtvgAAAAAAAAkcH_PPvNyBpqoa-j4G-86AA4rtwF_m7-6I24zpIcHfvpX', "PHP-Example/1.0");
        foreach ($bunch as &$rowData) {
            $rowData = $this->_customFieldsMapping($rowData);
            foreach ($this->_imagesArrayKeys as $image) {
                if (empty($rowData[$image])) {
                    continue;
                }
                $dispersionPath =
                    \Magento\Framework\File\Uploader::getDispretionPath($rowData[$image]);
                $importImages = explode($this->getMultipleValueSeparator(), $rowData[$image]);
                foreach ($importImages as $importImage) {
                    $imageSting = mb_strtolower(
                        $dispersionPath . '/' . preg_replace('/[^a-z0-9\._-]+/i', '', $importImage)
                    );
                    $filePath = '/var/www/local-magento2.com/magento2/pub/media/import/dropbox' . $imageSting;
                    $dirname = dirname($filePath);
                    if (!is_dir($dirname))
                    {
                        mkdir($dirname, 0775, true);
                    }
                    $f = fopen($filePath, 'w+b');
                    $fileMetadata = $dbxClient->getFile('/import/' . $importImage, $f);
                    $rowData[$image] = 'dropbox' . $imageSting;
                    fclose($f);
                }
            }
        }
        return $bunch;
    }

    /**
     * Retrieving images from all columns and rows
     *
     * @param $bunch
     * @return array
     */
    protected function _getAllBunchImages($bunch)
    {
        $allImagesFromBunch = [];
        foreach ($bunch as $rowData) {
            $rowData = $this->_customFieldsMapping($rowData);
            foreach ($this->_imagesArrayKeys as $image) {
                if (empty($rowData[$image])) {
                    continue;
                }
                $dispersionPath =
                    \Magento\Framework\File\Uploader::getDispretionPath($rowData[$image]);
                $importImages = explode($this->getMultipleValueSeparator(), $rowData[$image]);
                foreach ($importImages as $importImage) {
                    $imageSting = mb_strtolower(
                        $dispersionPath . '/' . preg_replace('/[^a-z0-9\._-]+/i', '', $importImage)
                    );
                    if(isset($this->_parameters['import_source']) && $this->_parameters['import_source'] == 'dropbox') {
                        $allImagesFromBunch['dropbox' . $imageSting] = $imageSting;
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
        foreach ($this->_fieldsMap as $systemFieldName => $fileFieldName) {
            if (isset($rowData[$fileFieldName])) {
                $rowData[$systemFieldName] = $rowData[$fileFieldName];
            }
        }

        $rowData = $this->_parseAdditionalAttributes($rowData);

        $rowData = $this->_setStockUseConfigFieldsValues($rowData);
        if (isset($rowData['status'])) {
            if (($rowData['status'] == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) || $rowData['status'] == 'yes') {
                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
            } else {
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
        if (empty($rowData['additional_attributes'])) {
            return $rowData;
        }

        $attributeNameValuePairs = explode($this->getMultipleValueSeparator(), $rowData['additional_attributes']);
        foreach ($attributeNameValuePairs as $attributeNameValuePair) {
            $nameAndValue = explode(self::PAIR_NAME_VALUE_SEPARATOR, $attributeNameValuePair);
            if (!empty($nameAndValue)) {
                $rowData[$nameAndValue[0]] = isset($nameAndValue[1]) ? $nameAndValue[1] : '';
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
        $useConfigFields = array();
        foreach ($rowData as $key => $value) {
            if (isset($this->defaultStockData[$key]) && isset($this->defaultStockData[self::INVENTORY_USE_CONFIG_PREFIX . $key]) && !empty($value)) {
                $useConfigFields[self::INVENTORY_USE_CONFIG_PREFIX . $key] = ($value == self::INVENTORY_USE_CONFIG) ? 1 : 0;
            }
        }
        $rowData = array_merge($rowData, $useConfigFields);
        return $rowData;
    }
}