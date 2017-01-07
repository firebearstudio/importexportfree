<?php
namespace Firebear\ImportExport\Helper;

/**
 * Class Data
 * @package Firebear\ImportExport\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Firebear\ImportExport\Model\Source\Factory
     */
    protected $_sourceFactory;

    /**
     * @param \Firebear\ImportExport\Model\Source\Factory $sourceFactory
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Firebear\ImportExport\Model\Source\Factory $sourceFactory
    ){
        $this->_sourceFactory = $sourceFactory;
        parent::__construct($context);
    }

    protected function _prepareSourceClassName($sourceType)
    {
        return 'Firebear\ImportExport\Model\Source\Type\\' . ucfirst(strtolower($sourceType));
    }

    public function getSourceModelByType($sourceType)
    {
        $sourceClassName = $this->_prepareSourceClassName($sourceType);
        if ($sourceClassName && class_exists($sourceClassName)) {
            /** @var $source \Firebear\ImportExport\Model\Source\Type\AbstractType */
            $source = $this->_sourceFactory->create($sourceClassName);
            return $source;
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Import source type class for '" . $sourceType . "' is not exist.")
            );
        }
    }
}