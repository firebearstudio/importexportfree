<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Source\Type;

class Dropbox extends AbstractType
{
    protected $_code = 'dropbox';

    public function uploadSource()
    {
        if($client = $this->_getSourceClient()) {
            //$filePath = '/var/www/local-magento2.com/magento2/var/import/dropbox/test-dropbox.csv';
            $sourceFilePath = $this->getData($this->_code . '_file_path');
            $fileName = basename($sourceFilePath);
            $filePath = $this->_directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);

            try {
                $dirname = dirname($filePath);
                if (!is_dir($dirname))
                {
                    mkdir($dirname, 0775, true);
                }
                $f = fopen($filePath, 'w+b');
            } catch(\Exception $e) {
                throw new  \Magento\Framework\Exception\LocalizedException(__("Can't create local file /var/import/dropbox'. Please check files permissions."));
            }
            $fileMetadata = $client->getFile($sourceFilePath, $f);
            fclose($f);
            if($fileMetadata) {
                return $this->_directory->getRelativePath($this->getImportPath() . '/' . $fileName);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__("File not found on Dropbox"));
            }
        } else {
            throw new  \Magento\Framework\Exception\LocalizedException(__("Can't initialize %s client", $this->_code));
        }
    }

    public function importImage($importImage, $imageSting)
    {
        if($client = $this->_getSourceClient()) {
            $filePath = $this->_directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
            $dirname = dirname($filePath);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
            }
            $f = fopen($filePath, 'w+b');
            $filePath = $this->getImportFilePath();

            if($filePath) {
                $dir = dirname($filePath);
                $fileMetadata = $client->getFile($dir . '/' . $importImage, $f);
            }
            fclose($f);
        }
    }

    protected function _getSourceClient()
    {
        if(!$this->_client) {
            $accessToken = $this->_scopeConfig->getValue(
                'firebear_importexport/dropbox/token',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            if($accessToken) {
                $this->_client = new \Dropbox\Client($accessToken, "PHP-Example/1.0");
            }
        }

        return $this->_client;
    }
}