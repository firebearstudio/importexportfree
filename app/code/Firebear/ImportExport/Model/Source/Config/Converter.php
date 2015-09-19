<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function convert($source)
    {
        $result = [];
        /** @var \DOMNode $templateNode */
        foreach ($source->documentElement->childNodes as $typeNode) {
            if ($typeNode->nodeType != XML_ELEMENT_NODE) {
                continue;
            }
            $typeName = $typeNode->attributes->getNamedItem('name')->nodeValue;
            $typeLabel = $typeNode->attributes->getNamedItem('label')->nodeValue;
            $typeModel = $typeNode->attributes->getNamedItem('modelInstance')->nodeValue;
            $sortOrder = $typeNode->attributes->getNamedItem('sortOrder')->nodeValue;

            $result[$typeName] = [
                'label' => $typeLabel,
                'model' => $typeModel,
                'sort_order' => $sortOrder
            ];
            foreach ($typeNode->childNodes as $childNode) {
                if ($childNode->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }

                $result[$typeName]['fields'][$childNode->attributes->getNamedItem('name')->nodeValue] = array(
                    'id' => $childNode->attributes->getNamedItem('id')->nodeValue,
                    'label' => $childNode->attributes->getNamedItem('label')->nodeValue,
                    'type' => $childNode->attributes->getNamedItem('type')->nodeValue,
                    'required' => ($childNode->attributes->getNamedItem('required')) ? $childNode->attributes->getNamedItem('required')->nodeValue : false,
                );
            }
        }

        return $result;
    }

}