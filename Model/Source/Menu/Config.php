<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Menu;

/**
 * Class Config
 * @package Firebear\ImportExport\Model\Source
 */
class Config extends \Magento\Framework\Config\Data
{
    /**
     * Config constructor.
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param \Firebear\ImportExport\Model\AbstractReader $reader
     * @param null $cacheId
     */
    public function __construct(
        \Magento\Framework\Config\CacheInterface $cache,
        \Firebear\ImportExport\Model\AbstractReader $reader,
        $cacheId = null
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * @return array|mixed|null
     */
    public function getItems($name)
    {
        return $this->get('add/' . $name, []);
    }
}
