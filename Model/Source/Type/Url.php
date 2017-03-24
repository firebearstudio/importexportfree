<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\ReadInterface;

/**
 * Class Url
 */
class Url extends AbstractType
{
    /**
     * @var string
     */
    protected $_code = 'url';

    /**
     * @var string
     */
    protected $_fileName;

    /**
     * Download remote source file to temporary directory
     *
     * @return bool|string
     */
    public function uploadSource()
    {
        if($read = $this->_getSourceClient()) {
            $fileName = preg_replace('/[^a-z0-9\._-]+/i', '', $this->_fileName);
            $this->_directory->writeFile(
                $this->_directory->getRelativePath($this->getImportVarPath() . '/' . $fileName),
                $read->readAll()
            );

            return $this->_directory->getRelativePath($this->getImportPath() . '/' . $fileName);
        }

        return false;
    }

    /**
     * Download remote images to temporary media directory
     *
     * @param $importImage
     * @param $imageSting
     *
     * @return bool
     */
    public function importImage($importImage, $imageSting)
    {
        $filePath = $this->_directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
        $dirname = dirname($filePath);
        if(!is_dir($dirname)) {
            mkdir($dirname, 0775, true);
        }
        if(preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
            $url = str_replace($matches[0], '', $importImage);
        } else {
            $sourceFilePath = $this->getData($this->_code . '_file_path');
            $sourceDir = dirname($sourceFilePath);
            $url = $sourceDir . '/' . $importImage;
            if(preg_match('/\bhttps?:\/\//i', $url, $matches)) {
                $url = str_replace($matches[0], '', $url);
            }
        }
        if($url) {
            $read = $this->_readFactory->create($url, DriverPool::HTTP);
            $this->_directory->writeFile(
                $this->_directory->getRelativePath($filePath),
                $read->readAll()
            );
        }

        return true;
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param int $timestamp
     *
     * @return bool|int
     */
    public function checkModified($timestamp)
    {
        $fileName = $this->getData($this->_code . '_file_path');
        if(preg_match('/\bhttps?:\/\//i', $fileName, $matches)) {
            $url = str_replace($matches[0], '', $fileName);
            $read = $this->_readFactory->create($url, DriverPool::HTTP);
            if(!$this->_metadata) {
                $this->_metadata = $read->stat();
            }
            $modified = strtotime($this->_metadata['mtime']);

            return ($timestamp != $modified) ? $modified : false;
        }

        return false;
    }

    /**
     * Prepare and return Driver client
     *
     * @return ReadInterface
     */
    protected function _getSourceClient()
    {
        if(!$this->_fileName) {
            $this->_fileName = $this->getData($this->_code . '_file_path');
        }
        if(!$this->_client) {
            if(preg_match('/\bhttps?:\/\//i', $this->_fileName, $matches)) {
                $url = str_replace($matches[0], '', $this->_fileName);
                $this->_client = $this->_readFactory->create($url, DriverPool::HTTP);
            }
        }

        return $this->_client;
    }
}