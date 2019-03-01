<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Storage;

use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;

class DbStorage extends \Magento\UrlRewrite\Model\Storage\DbStorage
{
    /**
     * Save new url rewrites and remove old if exist. Template method
     *
     * @param \Magento\UrlRewrite\Service\V1\Data\UrlRewrite[]|array $urls
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function doReplace(array $urls) : void
    {
        foreach ($this->createFilterDataBasedOnUrls($urls) as $type => $urlData) {
            $urlData[UrlRewrite::ENTITY_TYPE] = $type;
            $this->deleteByData($urlData);
        }
        $data = [];
        foreach ($urls as $url) {
            $urlArray = $url->toArray();
            $urlPath = $urlArray['request_path'];
            $storeId = $urlArray['store_id'];
            $dataKey = $storeId . '..' . $urlPath;
            $data[$dataKey] = $urlArray;
        }

        $this->insertMultiple($data);
    }
}
