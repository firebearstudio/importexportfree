<?php

namespace Firebear\ImportExport\Helper;

use Firebear\ImportExport\Model\Source\Factory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /**
     * @var Factory
     */
    protected $_sourceFactory;

    /**
     * @param Factory $sourceFactory
     * @param Context $context
     */
    public function __construct(
        Context $context,
        Factory $sourceFactory
    ) {
        $this->_sourceFactory = $sourceFactory;
        parent::__construct($context);
    }

    /**
     * @param string $sourceType
     *
     * @return string
     */
    protected function _prepareSourceClassName($sourceType)
    {
        return 'Firebear\ImportExport\Model\Source\Type\\' . ucfirst(strtolower($sourceType));
    }

    /**
     * @param string $sourceType
     *
     * @return \Firebear\ImportExport\Model\Source\Type\AbstractType
     */
    public function getSourceModelByType($sourceType)
    {
        $sourceClassName = $this->_prepareSourceClassName($sourceType);
        if($sourceClassName && class_exists($sourceClassName)) {
            /** @var $source \Firebear\ImportExport\Model\Source\Type\AbstractType */
            $source = $this->_sourceFactory->create($sourceClassName);

            return $source;
        } else {
            throw new LocalizedException(__("Import source type class for '" . $sourceType . "' is not exist."));
        }
    }
}