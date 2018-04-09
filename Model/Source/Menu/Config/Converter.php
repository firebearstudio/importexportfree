<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Menu\Config;

use Magento\Framework\Config\ConverterInterface;

class Converter implements ConverterInterface
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $url;

    /**
     * Converter constructor.
     * @param \Magento\Backend\Model\UrlInterface $url
     */
    public function __construct(
        \Magento\Backend\Model\UrlInterface $url
    ) {
        $this->url = $url;
    }

    /**
     * @param \DOMDocument $source
     * @return array
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
            $full = $typeNode->attributes->getNamedItem('fullUrl')->nodeValue;
            $url = $typeNode->attributes->getNamedItem('href')->nodeValue;
            $fullUrl = '';
            if ($full == 'false') {
                $fullUrl = $this->url->getUrl($url);
            } else {
                $fullUrl = $url;
            }
            $result[$typeName] = [
                'label' => $typeLabel,
                'url' => $fullUrl,
                'ext' => $full
            ];
        }

        return $result;
    }
}
