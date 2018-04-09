<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

class Ftp extends AbstractType
{
    /**
     * @var string
     */
    protected $code = 'ftp';

    /**
     * Download remote source file to temporary directory
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        if ($client = $this->getSourceClient()) {
            $sourceFilePath = $this->getData($this->code . '_file_path');
            $fileName = basename($sourceFilePath);
            //return get_class($this->_directory);
            $filePath = $this->_directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
            $filesystem = new \Magento\Framework\Filesystem\Io\File();
            $filesystem->setAllowCreateFolders(true);
            $filesystem->checkAndCreateFolder($this->_directory->getAbsolutePath($this->getImportPath()));

            $result = $client->read($sourceFilePath, $filePath);

            if ($result) {
                return $this->_directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__("File not found"));
            }
        } else {
            throw new  \Magento\Framework\Exception\LocalizedException(__("Can't initialize %s client", $this->code));
        }
    }

    /**
     * Download remote images to temporary media directory
     *
     * @param $importImage
     * @param $imageSting
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importImage($importImage, $imageSting)
    {
        if ($client = $this->getSourceClient()) {
            $sourceFilePath = $this->getData($this->code . '_file_path');
            $sourceDirName = dirname($sourceFilePath);
            $filePath = $this->_directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
            $dirname = dirname($filePath);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
            }
            if ($filePath) {
                $result = $client->read($sourceDirName . '/' . $importImage, $filePath);
            }
        }
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param int $timestamp
     * @return bool|int
     */
    public function checkModified($timestamp)
    {
        if ($client = $this->getSourceClient()) {
            $sourceFilePath = $this->getData($this->code . '_file_path');

            if (!$this->_metadata) {
                $this->_metadata['modified'] = $client->mdtm($sourceFilePath);
            }

            $modified = $this->_metadata['modified'];

            return ($timestamp != $this->_metadata['modified']) ? $modified : false;
        }

        return false;
    }

    /**
     * Prepare and return FTP client
     *
     * @return \Firebear\ImportExport\Model\Filesystem\Io\Ftp
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getSourceClient()
    {
        if (!$this->getClient()) {
            error_log("d333");

            if ($this->getData('ftp_host') && $this->getData('ftp_port') && $this->getData('ftp_user') && $this->getData('ftp_password')) {
                $settings['host'] = $this->getData('ftp_host');
                $settings['port'] = $this->getData('ftp_port');
                $settings['user'] = $this->getData('ftp_user');
                $settings['password'] = $this->getData('ftp_password');
            } else {
                $settings = $this->_scopeConfig->getValue(
                    'firebear_importexport/ftp',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
            }
            error_log(json_encode($settings));
            $settings['passive'] = true;
            try {
                $connection = new \Firebear\ImportExport\Model\Filesystem\Io\Ftp();
                $connection->open(
                    $settings
                );
                $this->client = $connection;
            } catch (\Exception $e) {
                throw new  \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        }

        return $this->getClient();
    }
}