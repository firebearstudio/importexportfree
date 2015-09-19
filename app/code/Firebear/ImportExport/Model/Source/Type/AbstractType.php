<?php
/**
 * @copyright: Copyright © 2015 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

abstract class AbstractType extends \Magento\Framework\Object {

    protected $_scopeConfig;

    protected $_directory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
        //\Magento\Framework\Filesystem\Directory\Write $directory
    ){
        $this->_scopeConfig = $scopeConfig;
        //$this->_directory = $directory;
    }

    abstract function uploadSource();
}