<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Plugin\Model;

use Firebear\ImportExport\Helper\Data;
use Firebear\ImportExport\Model\Source\ConfigInterface;
use Firebear\ImportExport\Model\Source\Type\AbstractType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\HTTP\Adapter\FileTransferFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Export\Adapter\CsvFactory;
use Magento\ImportExport\Model\History;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\Entity\Factory;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\Indexer\Model\IndexerRegistry;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Import
 */
class Import extends \Magento\ImportExport\Model\Import
{
    /**
     * Limit displayed errors on Import History page.
     */
    const LIMIT_VISIBLE_ERRORS = 5;
    const CREATE_ATTRIBUTES_CONF_PATH = 'firebear_importexport/general/create_attributes';

    /**
     * @var ConfigInterface
     */
    protected $_config;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $_timezone;

    /**
     * @var AbstractType
     */
    protected $_source;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $directory;

    /**
     * @param ConfigInterface                                            $config
     * @param Data                                                       $helper
     * @param TimezoneInterface                                          $timezone
     * @param LoggerInterface                                            $logger
     * @param Filesystem                                                 $filesystem
     * @param \Magento\ImportExport\Helper\Data                          $importExportData
     * @param ScopeConfigInterface                                       $coreConfig
     * @param \Magento\ImportExport\Model\Import\ConfigInterface         $importConfig
     * @param Factory                                                    $entityFactory
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data      $importData
     * @param CsvFactory                                                 $csvFactory
     * @param FileTransferFactory                                        $httpFactory
     * @param UploaderFactory                                            $uploaderFactory
     * @param \Magento\ImportExport\Model\Source\Import\Behavior\Factory $behaviorFactory
     * @param IndexerRegistry                                            $indexerRegistry
     * @param History                                                    $importHistoryModel
     * @param \Magento\Framework\Stdlib\DateTime\DateTime
     * @param array                                                      $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ConfigInterface $config,
        Data $helper,
        TimezoneInterface $timezone,
        LoggerInterface $logger,
        Filesystem $filesystem,
        \Magento\ImportExport\Helper\Data $importExportData,
        ScopeConfigInterface $coreConfig,
        \Magento\ImportExport\Model\Import\ConfigInterface $importConfig,
        Factory $entityFactory,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        CsvFactory $csvFactory,
        FileTransferFactory $httpFactory,
        UploaderFactory $uploaderFactory,
        \Magento\ImportExport\Model\Source\Import\Behavior\Factory $behaviorFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        History $importHistoryModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $localeDate,
        array $data = []
    ) {
        $this->_config = $config;
        $this->_helper = $helper;
        $this->_timezone = $timezone;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        parent::__construct(
            $logger,
            $filesystem,
            $importExportData,
            $coreConfig,
            $importConfig,
            $entityFactory,
            $importData,
            $csvFactory,
            $httpFactory,
            $uploaderFactory,
            $behaviorFactory,
            $indexerRegistry,
            $importHistoryModel,
            $localeDate,
            $data
        );
    }

    /**
     * Prepare source type class name
     *
     * @param $sourceType
     *
     * @return string
     */
    protected function _prepareSourceClassName($sourceType)
    {
        return 'Firebear\ImportExport\Model\Source\Type\\' . ucfirst(strtolower($sourceType));
    }

    /**
     * @return mixed|null|string
     * @throws LocalizedException
     */
    public function uploadSource()
    {
        $result = null;
        if($this->getImportSource() && $this->getImportSource() != 'file') {
            $source = $this->getSource();
            try {
                $result = $source->uploadSource();
            } catch(\Exception $e) {
                throw new LocalizedException(__($e->getMessage()));
            }
        }
        if($result) {
            $sourceFileRelative = $this->directory->getRelativePath($result);
            $entity = $this->getEntity();
            $this->createHistoryReport($sourceFileRelative, $entity);

            return DirectoryList::VAR_DIR . '/' . $result;
        }

        return parent::uploadSource();
    }

    /**
     * Validates source file and returns validation result.
     *
     * @param AbstractSource $source
     *
     * @return bool
     */
    public function validateSource(AbstractSource $source)
    {
        $this->addLogComment(__('Begin data validation'));
        try {
            $adapter = $this->_getEntityAdapter()->setSource($source);
            $errorAggregator = $adapter->validateData();
        } catch(\Exception $e) {
            $errorAggregator = $this->getErrorAggregator();
            $errorAggregator->addError(
                AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION
                . '. ' . $e->getMessage(),
                ProcessingError::ERROR_LEVEL_CRITICAL,
                null,
                null,
                null,
                $e->getMessage()
            );
        }
        $messages = $this->getOperationResultMessages($errorAggregator);
        $this->addLogComment($messages);
        $result = !$errorAggregator->getErrorsCount();
        if($result) {
            $this->addLogComment(__('Import data validation is complete.'));
        } else {
            if($this->isReportEntityType()) {
                $this->importHistoryModel->load($this->importHistoryModel->getLastItemId());
                $summary = '';
                if($errorAggregator->getErrorsCount() > self::LIMIT_VISIBLE_ERRORS) {
                    $summary = __('Too many errors. Please check your debug log file.') . '<br />';
                } else {
                    if($this->getJobId()) {
                        $summary = __('Import job #' . $this->getJobId() . ' failed.') . '<br />';
                    }
                    foreach($errorAggregator->getRowsGroupedByErrorCode() as $errorMessage => $rows) {
                        $error = $errorMessage . ' ' . __('in rows') . ': ' . implode(', ', $rows);
                        $summary .= $error . '<br />';
                    }
                }
                $date = $this->_timezone->formatDateTime(
                    new \DateTime(),
                    \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::MEDIUM,
                    null,
                    null
                );
                $summary .= '<i>' . $date . '</i>';
                $this->importHistoryModel->setSummary($summary);
                $this->importHistoryModel->setExecutionTime(History::IMPORT_FAILED);
                $this->importHistoryModel->save();
            }
        }

        return $result;
    }

    /**
     * Get import source by type.
     *
     * @return AbstractType
     */
    public function getSource()
    {
        if(!$this->_source) {
            $sourceType = $this->getImportSource();
            try {
                $this->_source = $this->_helper->getSourceModelByType($sourceType);
                $this->_source->setData($this->getData());
            } catch(\Exception $e) {
            }
        }

        return $this->_source;
    }

    /**
     * Get import history model
     *
     * @return mixed
     */
    public function getImportHistoryModel()
    {
        return $this->importHistoryModel;
    }
}