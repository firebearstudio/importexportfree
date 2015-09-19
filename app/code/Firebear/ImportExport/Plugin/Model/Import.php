<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Plugin\Model;

class Import extends \Magento\ImportExport\Model\Import {

    protected $_config;

    protected $_sourceFactory;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig
     * @param Import\ConfigInterface $importConfig
     * @param Import\Entity\Factory $entityFactory
     * @param Resource\Import\Data $importData
     * @param Export\Adapter\CsvFactory $csvFactory
     * @param FileTransferFactory $httpFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param Source\Import\Behavior\Factory $behaviorFactory
     * @param \Magento\Indexer\Model\IndexerRegistry $indexerRegistry
     * @param History $importHistoryModel
     * @param \Magento\Framework\Stdlib\DateTime\DateTime
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Firebear\ImportExport\Model\Source\ConfigInterface $config,
        \Firebear\ImportExport\Model\Source\Factory $sourceFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig,
        \Magento\ImportExport\Model\Import\ConfigInterface $importConfig,
        \Magento\ImportExport\Model\Import\Entity\Factory $entityFactory,
        \Magento\ImportExport\Model\Resource\Import\Data $importData,
        \Magento\ImportExport\Model\Export\Adapter\CsvFactory $csvFactory,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\ImportExport\Model\Source\Import\Behavior\Factory $behaviorFactory,
        \Magento\Indexer\Model\IndexerRegistry $indexerRegistry,
        \Magento\ImportExport\Model\History $importHistoryModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $localeDate,
        array $data = []
    ) {
        $this->_config = $config;
        $this->_sourceFactory = $sourceFactory;

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

    protected function _prepareSourceClassName($sourceType)
    {
        return 'Firebear\ImportExport\Model\Source\Type\\' . ucfirst(strtolower($sourceType));
    }

    public function uploadSource()
    {
        $result = null;

        if($sourceType = $this->getImportSource()) {
            $sourceClassName = $this->_prepareSourceClassName($sourceType);
            if ($sourceClassName && class_exists($sourceClassName)) {
                /** @var $source \Firebear\ImportExport\Model\Source\Type\AbstractType */
                $source = $this->_sourceFactory->create($sourceClassName);
                $source->setData($this->getData());

                try {
                    $result = $source->uploadSource();
                } catch(\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
                }
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("Import source type class for '%s' is not exist.", $sourceType)
                );
            }
        }

        if($result) {
            return $result;
        }

        return parent::uploadSource();
    }
}