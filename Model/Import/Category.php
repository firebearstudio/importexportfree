<?php
namespace Firebear\ImportExport\Model\Import;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;

class Category extends AbstractEntity
{

    /**
     * Delimiter in category path.
     */
    const DELIMITER_CATEGORY = '/';

    /**
     * Column category url key.
     */
    const COL_URL = 'url_key';

    /**
     * Column category name.
     */
    const COL_NAME = 'name';

    /**
     * Column category parent id.
     */
    const COL_PARENT = 'parent_id';

    /**
     * Column category path.
     */
    const COL_PATH = 'path';

    /**
     * Core event manager proxy
     *
     * @var ManagerInterface
     */
    protected $eventManager = null;

    /**
     * Flag for replace operation.
     *
     * @var null
     */
    protected $replaceFlag = null;

    /**
     * @var CategoryProcessor
     */
    protected $categoryProcessor;

    /**
     * @var CollectionFactory
     */
    protected $categoryColFactory;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * Categories text-path to ID hash.
     *
     * @var array
     */
    protected $categories = [];

    /**
     * @var array
     */
    protected $categoriesCache = [];

    /**
     * @param Data                             $jsonHelper
     * @param \Magento\ImportExport\Helper\Data                               $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data           $importData
     * @param Config                                       $config
     * @param ResourceConnection                       $resource
     * @param Helper                $resourceHelper
     * @param StringUtils                           $string
     * @param ProcessingErrorAggregatorInterface                              $errorAggregator
     * @param CollectionFactory $categoryColFactory
     * @param CategoryProcessor                                $categoryProcessor
     * @param CategoryFactory                          $categoryFactory
     * @param ManagerInterface                       $eventManager
     * @param CategoryRepositoryInterface                $categoryRepository
     */
    public function __construct(
        Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        Config $config,
        ResourceConnection $resource,
        Helper $resourceHelper,
        StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        CollectionFactory $categoryColFactory,
        CategoryProcessor $categoryProcessor,
        CategoryFactory $categoryFactory,
        ManagerInterface $eventManager,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->categoryColFactory = $categoryColFactory;
        $this->categoryProcessor = $categoryProcessor;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->eventManager = $eventManager;

        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator
        );

        $this->initCategories();
    }

    /**
     * Prepare all existing categories in array
     * @return $this
     */
    protected function initCategories()
    {
        if (empty($this->categories)) {
            $collection = $this->categoryColFactory->create();
            $collection->addAttributeToSelect('name')
                ->addAttributeToSelect('url_key')
                ->addAttributeToSelect('url_path');
            /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
            foreach ($collection as $category) {
                $structure = explode(self::DELIMITER_CATEGORY, $category->getPath());
                $pathSize = count($structure);

                $this->categoriesCache[$category->getId()] = $category;
                if ($pathSize > 1) {
                    $path = [];
                    for ($i = 1; $i < $pathSize; $i++) {
                        $path[] = $collection->getItemById((int)$structure[$i])->getName();
                    }
                    $index = implode(self::DELIMITER_CATEGORY, $path);
                    $this->categories[$index] = $category->getId();
                }
            }
        }
        return $this;
    }

    /**
     * Create Category entity from raw data.
     *
     * @throws \Exception
     * @return bool Result of operation.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _importData()
    {
        $this->_validatedRows = null;
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            $this->deleteCategories();
        } else {
            /**
             * If user select replace behavior all categories will be deleted first,
             * then new categories will be saved
             */
            $this->saveCategoriesData();
        }
        $this->eventManager->dispatch('catalog_category_import_finish_before', ['adapter' => $this]);
        return true;
    }

    /**
     * Delete categories is delete behavior is selected
     * @return $this
     */
    protected function deleteCategories()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $this->categoriesCache = [];

            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                if (isset($rowData['path']) && isset($this->categories[$rowData['path']])) {
                    $categoryId = (int)$this->categories[$rowData['path']];
                } else {
                    $categoryId = (int)$rowData['entity_id'];
                }

                $category = $this->categoryRepository->get($categoryId);
                $this->categoryRepository->delete($category);
            }
        }

        return $this;
    }

    /**
     * Delete all categories when replace behavior is selected
     * @return $this
     */
    protected function deleteAllCategories()
    {
        /**
         * Delete level 2 categories. All child categories will be deleted automatically.
         * Level 0 is magento 'Root Catalog' category.
         * Level 1 is default magento 'Default Category'.
         */
        foreach ($this->categoriesCache as $category) {
            if ($category->getLevel() == 2) {
                $this->categoryRepository->delete($category);
            }
        }

        /**
         * Clear categories cache.
         */
        $this->categories = [];
        $this->categoriesCache = [];

        /**
         * Re-init default categories.
         */
        $this->initCategories();

        return $this;
    }

    /**
     * Gather and save information about product entities.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function saveCategoriesData()
    {
        /**
         * Delete all categories if replace behavior is selected
         */
        if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            $this->deleteAllCategories();
        }

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $this->categoriesCache = [];

            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                $rowPath = null;
                if (strpos($rowData[self::COL_NAME], self::DELIMITER_CATEGORY) !== false) {
                    $rowPath = $rowData[self::COL_NAME];
                } elseif (isset($rowData[self::COL_PARENT])) {
                    $rowPath = (int) $rowData[self::COL_PARENT];
                } elseif (isset($rowData[self::COL_PATH])) {
                    $rowPath = $rowData[self::COL_PATH] . self::DELIMITER_CATEGORY . $rowData[self::COL_NAME];
                }

                if (!empty($rowPath)) {
                    if (is_int($rowPath)) {
                        $category = $this->categoryFactory->create();
                        if (!($parentCategory = isset($this->categoriesCache[$rowPath])
                                ? $this->categoriesCache[$rowPath] : null)
                        ) {
                            $parentCategory = $this->categoryFactory->create()->load($rowPath);
                        }
                        $category->setParentId($rowPath);
                        $category->setIsActive(true);
                        $category->setIncludeInMenu(true);
                        $category->setAttributeSetId($category->getDefaultAttributeSetId());
                        $category->addData($rowData);
                        $category->setPath($parentCategory->getPath());
                        $category->save();
                        $this->categoriesCache[$category->getId()] = $category;
                    } else {
                        if (!isset($this->categories[$rowPath])) {
                            $this->prepareCategoriesByPath($rowPath, $rowData);
                        } else {
                            $this->updateCategoriesByPath($rowPath, $rowData);
                        }
                    }
                }
            }

            $this->eventManager->dispatch(
                'catalog_category_import_bunch_save_after',
                ['adapter' => $this, 'bunch' => $bunch]
            );
        }
        return $this;
    }

    /**
     * Prepare new category by path.
     *
     * @param $rowPath
     * @param $rowData
     *
     * @return $this
     */
    protected function prepareCategoriesByPath($rowPath, $rowData)
    {
        $parentId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
        $pathParts = explode(self::DELIMITER_CATEGORY, $rowPath);
        $path = '';
        foreach ($pathParts as $pathPart) {
            $path .= $pathPart;
            if (!isset($this->categories[$path])) {
                $category = $this->categoryFactory->create();
                if (!($parentCategory = isset($this->categoriesCache[$parentId])
                    ? $this->categoriesCache[$parentId] : null)
                ) {
                    $parentCategory = $this->categoryFactory->create()->load($parentId);
                }
                $category->setParentId($parentId);
                $category->setIsActive(true);
                $category->setIncludeInMenu(true);
                $category->setAttributeSetId($category->getDefaultAttributeSetId());
                $category->addData($rowData);
                $category->setName($pathPart);
                $category->setPath($parentCategory->getPath());
                $category->save();
                $this->categoriesCache[$category->getId()] = $category;
                $this->categories[$path] = $category->getId();
            }

            $parentId = $this->categories[$path];
            $path .= self::DELIMITER_CATEGORY;
        }

        return $this;
    }

    /**
     * Update existing category by path.
     *
     * @param $rowPath
     * @param $rowData
     *
     * @return $this
     */
    protected function updateCategoriesByPath($rowPath, $rowData)
    {
        $categoryId = $this->categories[$rowPath];
        $category = $this->categoryFactory->create()->load($categoryId);

        /**
         * Avoid changing category name and path
         */
        if (isset($rowData[self::COL_NAME])) {
            unset($rowData[self::COL_NAME]);
        }

        if (isset($rowData[self::COL_PATH])) {
            unset($rowData[self::COL_PATH]);
        }

        $category->addData($rowData);
        $category->save();

        return $this;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateRow(array $rowData, $rowNum)
    {
        if (isset($this->_validatedRows[$rowNum])) {
            // check that row is already validated
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;
        $this->_processedEntitiesCount++;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * EAV entity type code getter.
     *
     * @abstract
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'catalog_category';
    }
}