<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source;

use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\Data;

/**
 * Class Config
 */
class Config extends Data implements ConfigInterface
{
    /**
     * @param Config\Reader  $reader
     * @param CacheInterface $cache
     * @param string         $cacheId
     */
    public function __construct(
        Config\Reader $reader,
        CacheInterface $cache,
        $cacheId = 'firebear_importexport_config'
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Get system configuration of source type by name
     *
     * @param string $name
     *
     * @return array|mixed|null
     */
    public function getType($name)
    {
        return $this->get('type/' . $name);
    }
}