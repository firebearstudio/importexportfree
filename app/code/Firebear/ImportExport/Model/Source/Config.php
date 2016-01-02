<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source;

class Config extends \Magento\Framework\Config\Data implements \Firebear\ImportExport\Model\Source\ConfigInterface {

    public function __construct(
        \Firebear\ImportExport\Model\Source\Config\Reader $reader,
        \Magento\Framework\Config\CacheInterface $cache,
        $cacheId = 'firebear_importexport_config'
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    public function getType($name) {
        return $this->get('type/' . $name);
    }
}