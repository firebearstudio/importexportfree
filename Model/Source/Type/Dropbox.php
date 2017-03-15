<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Dropbox\Client;
use Dropbox\Exception_BadResponseCode;
use Dropbox\Exception_OverQuota;
use Dropbox\Exception_RetryLater;
use Dropbox\Exception_ServerError;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Dropbox
 */
class Dropbox extends AbstractType
{
    /**
     * @var string
     */
    protected $_code = 'dropbox';

    /**
     * @var null
     */
    protected $_accessToken = null;

    /**
     * Download remote source file to temporary directory
     *
     * @return string
     * @throws Exception_BadResponseCode
     * @throws Exception_OverQuota
     * @throws Exception_RetryLater
     * @throws Exception_ServerError
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        if($client = $this->_getSourceClient()) {
            $sourceFilePath = $this->getData($this->_code . '_file_path');
            $fileName = basename($sourceFilePath);
            $filePath = $this->_directory->getAbsolutePath($this->getImportVarPath() . '/' . $fileName);
            try {
                $dirname = dirname($filePath);
                if(!is_dir($dirname)) {
                    mkdir($dirname, 0775, true);
                }
                $file = fopen($filePath, 'w+b');
            } catch(\Exception $e) {
                throw new  \Magento\Framework\Exception\LocalizedException(
                    __(
                        "Can't create local file /var/import/dropbox'. Please check files permissions. "
                        . $e->getMessage()
                    )
                );
            }
            $fileMetadata = $client->getFile($sourceFilePath, $file);
            fclose($file);
            if($fileMetadata) {
                return $this->_directory->getRelativePath($this->getImportPath() . '/' . $fileName);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__("File not found on Dropbox"));
            }
        } else {
            throw new  \Magento\Framework\Exception\LocalizedException(__("Can't initialize %s client", $this->_code));
        }
    }

    /**
     * Download remote images to temporary media directory
     *
     * @param $importImage
     * @param $imageSting
     *
     * @return mixed|void
     * @throws Exception_BadResponseCode
     * @throws Exception_OverQuota
     * @throws Exception_RetryLater
     * @throws Exception_ServerError
     */
    public function importImage($importImage, $imageSting)
    {
        if($client = $this->_getSourceClient()) {
            $filePath = $this->_directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
            $sourceFilePath = $this->getData($this->_code . '_file_path');
            $sourceDir = dirname($sourceFilePath);
            $dirname = dirname($filePath);
            if(!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
            }
            $file = fopen($filePath, 'w+b');
            if($filePath) {
                $client->getFile($sourceDir . '/' . $importImage, $file);
            }
            fclose($file);
        }
    }

    /**
     * Get access token
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        if(!$this->_accessToken) {
            /**
             * Data sent by cron job
             * @see \Firebear\ImportExport\Plugin\Model\Import::uploadSource()
             *
             * else get token from admin config if import processed directly via admin panel
             */
            if($token = $this->getData('access_token')) {
                $this->_accessToken = $token;
            } else {
                $this->_accessToken = $this->_scopeConfig->getValue(
                    'firebear_importexport/dropbox/token',
                    ScopeInterface::SCOPE_STORE
                );
            }
        }

        return $this->_accessToken;
    }

    /**
     * Set access token
     *
     * @param $token
     *
     * @return Dropbox
     */
    public function setAccessToken($token)
    {
        $this->_accessToken = $token;

        return $this;
    }

    /**
     * Prepare and return API client
     *
     * @return Client
     */
    protected function _getSourceClient()
    {
        if(!$this->_client) {
            $accessToken = $this->getAccessToken();
            if($accessToken) {
                $this->_client = new Client($accessToken, "PHP-Example/1.0");
            }
        }

        return $this->_client;
    }
}