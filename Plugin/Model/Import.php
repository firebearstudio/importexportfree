<?php
namespace Firebear\ImportExport\Plugin\Model;

class Import extends \Magento\ImportExport\Model\Import {

    public function uploadSource()
    {
        if($accessToken = $this->getDropboxAccessToken()) {
            $dbxClient = new \Dropbox\Client($accessToken, "PHP-Example/1.0");
            $filePath = '/var/www/local-magento2.com/magento2/var/import/test-dropbox.csv';
            $f = fopen($filePath, 'w+b');
            $fileMetadata = $dbxClient->getFile($this->getDropboxFilePath(), $f);
            fclose($f);
            if($fileMetadata) {
                return $filePath;
            }
        }

        return parent::uploadSource();
    }
}