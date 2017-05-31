<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Config;

use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Module\Dir\Reader;

class SchemaLocator implements SchemaLocatorInterface
{
    /**
     * Path to corresponding XSD file with validation rules for merged config
     *
     * @var string
     */
    protected $schema = null;

    /**
     * Path to corresponding XSD file with validation rules for separate config files
     *
     * @var string
     */
    protected $perFileSchema = null;

    /**
     * @param Reader $moduleReader
     */
    public function __construct(
        Reader $moduleReader
    ) {
        $etcDir = $moduleReader->getModuleDir('etc', 'Firebear_ImportExport');
        $this->schema = $etcDir . '/source_types.xsd';
        $this->perFileSchema = $etcDir . '/source_types.xsd';
    }

    /**
     * Get path to merged config schema
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Get path to pre file validation schema
     *
     * @return string
     */
    public function getPerFileSchema()
    {
        return $this->perFileSchema;
    }
}