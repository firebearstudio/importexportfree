<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Abstract class for import source types
 * @package Firebear\ImportExport\Model\Source\Type
 */
abstract class AbstractType extends \Magento\Framework\DataObject {

    /**
     * Temp directory for downloaded files
     */
    const IMPORT_DIR = 'import';

    /**
     * Temp directory for downloaded images
     */
    const MEDIA_IMPORT_DIR = 'pub/media/import';

    /**
     * Source type code
     * @var string
     */
    protected $_code;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_directory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;

    /**
     * @var \Magento\Framework\Filesystem\File\ReadFactory
     */
    protected $_readFactory;

    /**
     * @var array
     */
    protected $_metadata = [];

    protected $_client;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem                      $filesystem
     * @param \Magento\Framework\Filesystem\File\ReadFactory     $readFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\File\ReadFactory $readFactory
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_filesystem = $filesystem;
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->_readFactory = $readFactory;
    }

    /**
     * Prepare temp dir for import files
     *
     * @return string
     */
    protected function getImportPath()
    {
        return self::IMPORT_DIR . '/' . $this->_code;
    }

    /**
     * @return string
     */
    protected function getImportVarPath()
    {
        return DirectoryList::VAR_DIR . '/' . $this->getImportPath();
    }

    /**
     * Prepare temp dir for import images
     *
     * @return string
     */
    protected function getMediaImportPath()
    {
        return self::MEDIA_IMPORT_DIR . '/' . $this->_code;
    }

    /**
     * Get file path
     *
     * @return bool|string
     */
    public function getImportFilePath() {
        if ($sourceType = $this->getImportSource()) {
            $filePath = $this->getData($sourceType . '_file_path');
            return $filePath;
        }

        return false;
    }

    /**
     * Get source type code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * @param $client
     */
    public function setClient($client)
    {
        $this->_client = $client;
    }

    /**
     * @return mixed
     */
    abstract function uploadSource();

    /**
     * @param $importImage
     * @param $imageSting
     *
     * @return mixed
     */
    abstract function importImage($importImage, $imageSting);

    /**
     * @return mixed
     */
    abstract protected function _getSourceClient();
}