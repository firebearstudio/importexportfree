<?php
/**
 * UrlKeyManager
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product;

use Firebear\ImportExport\Api\UrlKeyManagerInterface;

/**
 * Class UrlKeyManager
 * @package Firebear\ImportExport\Model\Import\Product
 * @api
 * @since 3.1.4
 */
class UrlKeyManager implements UrlKeyManagerInterface
{
    protected $importUrlKeys = [];

    /**
     * @param $sku
     * @param $urlKey
     *
     * @return $this|mixed
     */
    public function addUrlKeys($sku, $urlKey)
    {
        if (!isset($this->importUrlKeys[$urlKey])) {
            $this->importUrlKeys[$urlKey] = $sku;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getUrlKeys()
    {
        return $this->importUrlKeys;
    }

    /**
     * @param $sku
     * @param $urlKey
     *
     * @return bool|mixed
     */
    public function isUrlKeyExist($sku, $urlKey)
    {
        if (isset($this->importUrlKeys[$urlKey]) && $this->importUrlKeys[$urlKey] !== $sku) {
            return true;
        }
        return false;
    }
}
