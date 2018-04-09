<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model;

use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\Config\Reader\Filesystem;
use Magento\Framework\Config\ValidationStateInterface;

class AbstractReader extends Filesystem
{
    /**
     * @var array
     */
    protected $_idAttributes = [
        '/config/type' => 'name'
    ];

    /**
     * AbstractReader constructor.
     * @param FileResolverInterface $fileResolver
     * @param ValidationStateInterface $validationState
     * @param array $idAttributes
     * @param string $domDocumentClass
     * @param string $defaultScope
     * @param string $fileName
     * @param \Magento\Framework\Config\ConverterInterface|null $converter
     * @param \Magento\Framework\Config\SchemaLocatorInterface|null $schemaLocator
     */
    public function __construct(
        FileResolverInterface $fileResolver,
        ValidationStateInterface $validationState,
        $idAttributes = [],
        $domDocumentClass = 'Magento\Framework\Config\Dom',
        $defaultScope = 'global',
        $fileName = '',
        \Magento\Framework\Config\ConverterInterface $converter = null,
        \Magento\Framework\Config\SchemaLocatorInterface $schemaLocator = null
    ) {
        $this->_fileResolver = $fileResolver;
        $this->_converter = $converter;
        $this->_fileName = $fileName;
        $this->_idAttributes = array_replace($this->_idAttributes, $idAttributes);
        $this->_schemaFile = $schemaLocator->getSchema();
        $this->validationState = $validationState;
        $this->_perFileSchema = $schemaLocator->getPerFileSchema() && $validationState->isValidationRequired()
            ? $schemaLocator->getPerFileSchema() : null;
        $this->_domDocumentClass = $domDocumentClass;
        $this->_defaultScope = $defaultScope;
    }
}
