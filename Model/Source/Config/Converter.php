<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Config;

use Magento\Framework\Config\ConverterInterface;

/**
 * Class Converter
 */
class Converter implements ConverterInterface
{
    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function convert($source)
    {
        $result = [];
        /** @var \DOMNode $templateNode */
        foreach($source->documentElement->childNodes as $typeNode) {
            if($typeNode->nodeType != XML_ELEMENT_NODE) {
                continue;
            }
            $typeName = $typeNode->attributes->getNamedItem('name')->nodeValue;
            $typeLabel = $typeNode->attributes->getNamedItem('label')->nodeValue;
            $typeModel = $typeNode->attributes->getNamedItem('modelInstance')->nodeValue;
            $sortOrder = $typeNode->attributes->getNamedItem('sortOrder')->nodeValue;
            $result[$typeName] = [
                'label'      => $typeLabel,
                'model'      => $typeModel,
                'sort_order' => $sortOrder,
            ];
            foreach($typeNode->childNodes as $childNode) {
                if($childNode->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }
                $result[$typeName]['fields'][$childNode->attributes->getNamedItem('name')->nodeValue] = [
                    'id'       => $childNode->attributes->getNamedItem('id')->nodeValue,
                    'label'    => $childNode->attributes->getNamedItem('label')->nodeValue,
                    'type'     => $childNode->attributes->getNamedItem('type')->nodeValue,
                    'required' => ($childNode->attributes->getNamedItem('required'))
                        ? $childNode->attributes->getNamedItem('required')->nodeValue : false,
                ];
            }
        }

        return $result;
    }
}