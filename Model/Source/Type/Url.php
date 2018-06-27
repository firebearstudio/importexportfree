<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\Filesystem\DriverPool;

/**
 * Class Url
 * @package Firebear\ImportExport\Model\Source\Type
 */
class Url extends AbstractType
{
    /**
     * @var string
     */
    protected $code = 'url';

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
        error_log("bb1");
        if ($read = $this->getSourceClient()) {
            error_log("bb2");
            $fileName = preg_replace('/[^a-z0-9\._-]+/i', '', $this->_fileName);
            $fileName = str_replace("%", "_", $fileName);
            $this->_directory->writeFile(
                $this->_directory->getRelativePath($this->getImportPath() . '/' . $fileName),
                $read->readAll()
            );

            return $this->_directory->getAbsolutePath() . $this->_directory->getRelativePath($this->getImportPath() . '/' . $fileName);
        }

        return false;
    }

    /**
     * Download remote images to temporary media directory
     *
     * @param $importImage
     * @param $imageSting
     * @return bool
     */
    public function importImage($importImage, $imageSting)
    {
        $filePath = $this->_directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
        $dirname = dirname($filePath);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0775, true);
        }

        if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
            $url = str_replace($matches[0], '', $importImage);
        } else {
            $sourceFilePath = $this->getData($this->code . '_file_path');
            $sourceDir = dirname($sourceFilePath);
            $url = $sourceDir . '/' . $importImage;
            if (preg_match('/\bhttps?:\/\//i', $url, $matches)) {
                $url = str_replace($matches[0], '', $url);
            }
        }

        if ($url) {
            $driver = $this->getProperDriverCode($matches);
            $read = $this->_readFactory->create($url, $driver);
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
     * @return bool|int
     */
    public function checkModified($timestamp)
    {
        $fileName = $this->getData($this->code . '_file_path');
        if (preg_match('/\bhttps?:\/\//i', $fileName, $matches)) {
            $url = str_replace($matches[0], '', $fileName);
            $read = $this->_readFactory->create($url, DriverPool::HTTP);

            if (!$this->_metadata) {
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
     * @return \Magento\Framework\Filesystem\File\ReadInterface
     */
    protected function getSourceClient()
    {
        if (!$this->_fileName) {
            $this->_fileName = $this->getData($this->code . '_file_path');
        }

        if (!$this->client) {
            if (preg_match('/\bhttps?:\/\//i', $this->_fileName, $matches)) {
                $url = str_replace($matches[0], '', $this->_fileName);
                $driver = $this->getProperDriverCode($matches);
                $this->client = $this->_readFactory->create($url, $driver);
            }
        }

        return $this->client;
    }

    protected function getProperDriverCode($matches)
    {
        if (is_array($matches)) {
            return (false === strpos($matches[0], 'https'))
                ? DriverPool::HTTP
                : DriverPool::HTTPS;
        } else {
            return DriverPool::HTTP;
        }
    }
}