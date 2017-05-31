<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source;

interface ConfigInterface
{
    /**
     * Get configuration of source type by name
     *
     * @param string $name
     * @return array
     */
    public function getType($name);
}