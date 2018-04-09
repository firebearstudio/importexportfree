<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Source\Type;

class Dropbox extends AbstractType
{
    /**
     * Source code
     *
     * @var string
     */
    protected $code = 'dropbox';

    /**
     * Dropbox app key
     *
     * @var string
     */
    protected $appKey;

    /**
     * Dropbox app secret
     *
     * @var string
     */
    protected $appSecret;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * Download remote source file to temporary directory
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        if ($client = $this->getSourceClient()) {
            //$filePath = '/var/www/local-magento2.com/magento2/var/import/dropbox/test-dropbox.csv';
            $sourceFilePath = $this->getData($this->code . '_file_path');
            $fileName = basename($sourceFilePath);
            $filePath = $this->_directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);

            try {
                $dirname = dirname($filePath);
                if (!is_dir($dirname)) {
                    mkdir($dirname, 0775, true);
                }
                $fileResource = fopen($filePath, 'w+b');
            } catch (\Exception $e) {
                throw new  \Magento\Framework\Exception\LocalizedException(__(
                    "Can't create local file /var/import/dropbox'. Please check files permissions. "
                    . $e->getMessage()
                ));
            }

            try {
                $fileMetadata = $client->download($sourceFilePath, $filePath);
            } catch (\Kunnu\Dropbox\Exceptions\DropboxClientException $e) {
                if ($e->getCode() == 0) {
                    $response = $this->jsonHelper->jsonDecode($e->getMessage());
                    throw new \Magento\Framework\Exception\LocalizedException(__(
                        "Dropbox API Exception: " . $response['error_summary']
                    ));
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(__(
                        "Dropbox API Exception: " . $e->getMessage()
                    ));
                }
            }

            fclose($fileResource);
            if ($fileMetadata) {
                return $this->_directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__("File not found on Dropbox"));
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
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importImage($importImage, $imageSting)
    {
        if ($client = $this->getSourceClient()) {
            $filePath = $this->_directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
            $sourceDir = $this->getData($this->code . '_import_images_file_dir');
            $dirname = dirname($filePath);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
            }
            $fileResource = fopen($filePath, 'w+b');
            if ($this->getData($this->code . '_images_on_dropbox')) {
                try {
                    $client->download($sourceDir . $importImage, $filePath);
                } catch (\Kunnu\Dropbox\Exceptions\DropboxClientException $e) {
                    if ($e->getCode() == 0) {
                        $response = $this->jsonHelper->jsonDecode($e->getMessage());
                        throw new \Magento\Framework\Exception\LocalizedException(__(
                            "Dropbox API Exception: " . $response['error_summary']
                        ));
                    } else {
                        throw new \Magento\Framework\Exception\LocalizedException(__(
                            "Dropbox API Exception: " . $e->getMessage()
                        ));
                    }
                }
            }
            fclose($fileResource);
        }

        return $this;
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
                $this->_metadata = $client->getMetadata($sourceFilePath);
            }

            $modified = strtotime($this->_metadata['modified']);

            return ($timestamp != $modified) ? $modified : false;
        }

        return false;
    }

    /**
     * Get access token
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        if (!$this->accessToken) {
            $this->accessToken = $this->_scopeConfig->getValue(
                'firebear_importexport/dropbox/token',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }

        return $this->accessToken;
    }

    /**
     * Get dropbox application key
     *
     * @return string|null
     */
    public function getAppKey()
    {
        if (!$this->appKey) {
            $this->appKey = $this->_scopeConfig->getValue(
                'firebear_importexport/dropbox/app_key',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }

        return $this->appKey;
    }

    /**
     * Get dropbox application secret
     *
     * @return string|null
     */
    public function getAppSecret()
    {
        if (!$this->appSecret) {
            $this->appSecret = $this->_scopeConfig->getValue(
                'firebear_importexport/dropbox/app_secret',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }

        return $this->appSecret;
    }

    /**
     * Prepare and return API client
     *
     * @return \Kunnu\Dropbox\Dropbox
     */
    protected function getSourceClient()
    {
        if (!$this->client) {
            $appKey = $this->getAppKey();
            $appSecret = $this->getAppSecret();
            $accessToken = $this->getAccessToken();
            if ($accessToken) {
                $app = new \Kunnu\Dropbox\DropboxApp($appKey, $appSecret, $accessToken);
                $this->client = new \Kunnu\Dropbox\Dropbox($app);
            }
        }

        return $this->client;
    }
}