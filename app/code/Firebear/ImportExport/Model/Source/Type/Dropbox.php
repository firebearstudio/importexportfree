<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Source\Type;

class Dropbox extends AbstractType
{
    public function uploadSource()
    {
        $accessToken = $this->_scopeConfig->getValue(
            'firebear_importexport/dropbox/token',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if($accessToken) {
            $dbxClient = new \Dropbox\Client($accessToken, "PHP-Example/1.0");
            $filePath = '/var/www/local-magento2.com/magento2/var/import/dropbox/test-dropbox.csv';
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
            $fileMetadata = $dbxClient->getFile($this->getDropboxFilePath(), $f);
            fclose($f);
            if($fileMetadata) {
                return '/var/import/dropbox/test-dropbox.csv';
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__("File not found on Dropbox"));
            }
        }
    }
}