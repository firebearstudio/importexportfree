<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

class Ftp extends AbstractType
{
    protected $_code = 'ftp';

    public function uploadSource()
    {
        if($client = $this->_getSourceClient()) {
            $sourceFilePath = $this->getData($this->_code . '_file_path');
            $fileName = basename($sourceFilePath);
            $filePath = $this->_directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
            $result = $client->read($sourceFilePath, $filePath);

            if($result) {
                return $this->_directory->getRelativePath($this->getImportPath() . '/' . $fileName);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__("File not found"));
            }
        } else {
            throw new  \Magento\Framework\Exception\LocalizedException(__("Can't initialize %s client", $this->_code));
        }
    }

    public function importImage($importImage, $imageSting)
    {
        if($client = $this->_getSourceClient()) {
            $sourceFilePath = $this->getData($this->_code . '_file_path');
            $sourceDirName = dirname($sourceFilePath);
            $filePath = $this->_directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
            $dirname = dirname($filePath);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
            }
            if($filePath) {
                $result = $client->read($sourceDirName . '/' . $importImage, $filePath);
            }
        }
    }

    protected function _getSourceClient()
    {
        if(!$this->_client) {
            $settings = $this->_scopeConfig->getValue(
                'firebear_importexport/ftp',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $settings['passive'] = true;
            try {
                $connection = new \Magento\Framework\Filesystem\Io\Ftp();
                $connection->open(
                    $settings
                );
                $this->_client = $connection;
            } catch(\Exception $e){
                throw new  \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }

        }

        return $this->_client;
    }
}