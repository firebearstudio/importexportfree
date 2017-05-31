<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;

/**
 * Abstract class for import source types
 * @package Firebear\ImportExport\Model\Source\Type
 */
abstract class AbstractType extends DataObject
{

    /**
     * Temp directory for downloaded files
     */
    const IMPORT_DIR = 'var/import';

    /**
     * Temp directory for downloaded images
     */
    const MEDIA_IMPORT_DIR = 'pub/media/import';

    /**
     * Source type code
     * @var string
     */
    protected $code;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

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

    /**
     * @var mixed
     */
    protected $client;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem                      $filesystem
     * @param \Magento\Framework\Filesystem\File\ReadFactory     $readFactory
     * @param \Magento\Framework\Json\Helper\Data                $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\File\ReadFactory $readFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_filesystem = $filesystem;
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->_readFactory = $readFactory;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Prepare temp dir for import files
     *
     * @return string
     */
    protected function getImportPath()
    {
        return self::IMPORT_DIR . '/' . $this->code;
    }

    /**
     * Prepare temp dir for import images
     *
     * @return string
     */
    protected function getMediaImportPath()
    {
        return self::MEDIA_IMPORT_DIR . '/' . $this->code;
    }

    /**
     * Get file path
     *
     * @return bool|string
     */
    public function getImportFilePath()
    {
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
        return $this->code;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    abstract function uploadSource();

    abstract function importImage($importImage, $imageSting);

    abstract function checkModified($timestamp);

    abstract protected function getSourceClient();
}