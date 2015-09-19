<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Config;


class SchemaLocator implements \Magento\Framework\Config\SchemaLocatorInterface
{
    /**
     * Path to corresponding XSD file with validation rules for merged config
     *
     * @var string
     */
    protected $_schema = null;

    /**
     * Path to corresponding XSD file with validation rules for separate config files
     *
     * @var string
     */
    protected $_perFileSchema = null;

    /**
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     */
    public function __construct(
        \Magento\Framework\Module\Dir\Reader $moduleReader
    ) {
        $etcDir = $moduleReader->getModuleDir('etc', 'Firebear_ImportExport');
        $this->_schema = $etcDir . '/source_types.xsd';
        $this->_perFileSchema = $etcDir . '/source_types.xsd';
    }

    /**
     * Get path to merged config schema
     *
     * @return string
     */
    public function getSchema() {
        return $this->_schema;
    }

    /**
     * Get path to pre file validation schema
     *
     * @return string
     */
    public function getPerFileSchema() {
        return $this->_perFileSchema;
    }
}