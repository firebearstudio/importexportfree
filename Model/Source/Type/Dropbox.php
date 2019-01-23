<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

class Dropbox extends AbstractType
{
    /**
     * @var string
     */
    protected $code = 'dropbox';

    /**
     * @var null
     */
    protected $accessToken = null;

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        $sourceFilePath = $this->getData($this->code . '_file_path');
        $fileName = basename($sourceFilePath);
        $filePath = $this->_directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
        try {
            $dirname = dirname($filePath);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    "Can't create local file /var/import/dropbox'. Please check files permissions. "
                    . $e->getMessage()
                )
            );
        }
        $fileContent = $this->downloadFile($sourceFilePath);
        file_put_contents($filePath, $fileContent);
        if ($fileContent) {
            return $this->getImportPath() . '/' . $fileName;
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__("File not found on Dropbox"));
        }
    }

    /**
     * @param $importImage
     * @param $imageSting
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importImage($importImage, $imageSting)
    {
        if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
            $this->setUrl($importImage, $imageSting, $matches);
        } else {
            $filePath = $this->_directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
            $dirname = dirname($filePath);
            $sourceDir = $this->getData($this->code . '_import_images_file_dir');
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
            }
            try {
                $fileContent = $this->downloadFile($sourceDir . $importImage);
                file_put_contents($filePath, $fileContent);
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__(
                    "Dropbox API Exception: " . $e->getMessage()
                ));
            }
        }
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

        $sourceFilePath = $this->getData($this->code . '_file_path');

        if (!$this->_metadata) {
            $this->_metadata = $this->getMetadata($sourceFilePath);
        }

        $modified = strtotsime($this->_metadata['client_modified']);

        return ($timestamp != $modified) ? $modified : false;
    }

    /**
     * Set access token
     *
     * @param $token
     */
    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * @return bool
     */
    protected function getSourceClient()
    {
        $this->client = false;
        return $this->client;
    }

    /**
     * Get file content from dropbox
     *
     * @param $filePath
     *
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function downloadFile($filePath)
    {
        $url = 'https://content.dropboxapi.com/2/files/download';

        $resource = curl_init($url);

        curl_setopt($resource, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->getData($this->code . '_access_token'),
            'Dropbox-API-Arg: {"path": "' . $filePath . '"}'
        ]);
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($resource, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($resource);
        curl_close($resource);

        if ($json = json_decode($result, true)) {
            if (!empty($json['error']['.tag'])) {
                $tag = $json['error']['.tag'];
                if ($tag == 'invalid_access_token') {
                    $error = "Invalid Dropbox access token";
                } elseif ($tag == 'path') {
                    $error = "File not found on Dropbox: " . $filePath;
                } else {
                    $error = "Dropbox api error: " . $result;
                }
                throw new \Magento\Framework\Exception\LocalizedException(__($error));
            }
        }

        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * Get file metadata
     *
     * @param $filePath
     *
     * @return bool|mixed
     */
    protected function getMetadata($filePath)
    {
        $url = 'https://api.dropboxapi.com/2/files/get_metadata';

        $resource = curl_init($url);

        curl_setopt($resource, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->getData($this->code . '_access_token'),
            'Content-Type: application/json',
        ]);
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($resource, CURLOPT_POST, true);
        curl_setopt($resource, CURLOPT_POSTFIELDS, '{"path": "' . $filePath . '"}');
        curl_setopt($resource, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($resource);
        curl_close($resource);

        if ($result) {
            return json_decode($result, true);
        }

        return false;
    }
}
