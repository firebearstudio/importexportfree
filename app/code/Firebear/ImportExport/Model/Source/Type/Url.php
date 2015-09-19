<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Magento\Framework\Filesystem\DriverPool;

class Url extends AbstractType
{
    protected $_code = 'url';

    public function uploadSource()
    {
        $fileName = $this->getData($this->_code . '_file_path');
        if (preg_match('/\bhttps?:\/\//i', $fileName, $matches)) {
            $url = str_replace($matches[0], '', $fileName);
            $read = $this->_readFactory->create($url, DriverPool::HTTP);
            $fileName = preg_replace('/[^a-z0-9\._-]+/i', '', $fileName);
            $this->_directory->writeFile(
                $this->_directory->getRelativePath($this->getImportPath() . '/' . $fileName),
                $read->readAll()
            );

            return $this->_directory->getRelativePath($this->getImportPath() . '/' . $fileName);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__("Please, provide correct URL"));
        }
    }

    public function importImage($importImage, $imageSting)
    {
        return false;
    }

    protected function _getSourceClient()
    {
        return $this->_client;
    }
}