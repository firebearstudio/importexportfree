<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\App\Filesystem\DirectoryList;

abstract class AbstractType extends \Magento\Framework\Object {

    const IMPORT_DIR = 'var/import';

    const MEDIA_IMPORT_DIR = 'pub/media/import';

    protected $_code;

    protected $_scopeConfig;

    protected $_directory;

    protected $_client;

    protected $_filesystem;

    protected $_readFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\File\ReadFactory $readFactory
    ){
        $this->_scopeConfig = $scopeConfig;
        $this->_filesystem = $filesystem;
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->_readFactory = $readFactory;
    }

    protected function getImportPath()
    {
        return self::IMPORT_DIR . '/' . $this->_code;
    }

    protected function getMediaImportPath()
    {
        return self::MEDIA_IMPORT_DIR . '/' . $this->_code;
    }

    public function getImportFilePath() {
        if($sourceType = $this->getImportSource()) {
            $filePath = $this->getData($sourceType . '_file_path');
            return $filePath;
        }

        return false;
    }

    public function getCode()
    {
        return $this->_code;
    }

    abstract function uploadSource();

    abstract function importImage($importImage, $imageSting);

    abstract protected function _getSourceClient();
}