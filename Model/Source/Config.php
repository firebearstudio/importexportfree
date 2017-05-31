<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source;

/**
 * Class Config
 * @package Firebear\ImportExport\Model\Source
 */
class Config extends \Magento\Framework\Config\Data implements \Firebear\ImportExport\Model\Source\ConfigInterface
{

    /**
     * @param Config\Reader                            $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param string                                   $cacheId
     */
    public function __construct(
        \Firebear\ImportExport\Model\Source\Config\Reader $reader,
        \Magento\Framework\Config\CacheInterface $cache,
        $cacheId = 'firebear_importexport_config'
    ) {
        parent::__construct($reader, $cache, $cacheId);
    }

    /**
     * Get system configuration of source type by name
     *
     * @param string $name
     * @return array|mixed|null
     */
    public function getType($name)
    {
        return $this->get('type/' . $name, []);
    }
}