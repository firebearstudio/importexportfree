<?php
/**
 * Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 */
namespace Firebear\ImportExport\Plugin\Model\Import\Product;

use Firebear\ImportExport\Model\Import;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\Validator as BaseValidator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Validator
 * Rewrite this class to allow import attribute values on the fly.
 *
 * @package Firebear\ImportExport\Plugin\Model\Import\Product
 */
class Validator extends BaseValidator
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var AttributeFactory
     */
    protected $prodAttrFac;

    /**
     * @param StringUtils                     $string
     * @param RowValidatorInterface[]                                   $validators
     * @param ScopeConfigInterface        $scopeConfig
     * @param AttributeFactory $prodAttrFac
     */
    public function __construct(
        StringUtils $string,
        $validators = [],
        ScopeConfigInterface $scopeConfig,
        AttributeFactory $prodAttrFac
    ) {
        parent::__construct($string, $validators);
        $this->scopeConfig = $scopeConfig;
        $this->prodAttrFac = $prodAttrFac;
    }

    /**
     * Rewrite method which allow create attributes & values on the fly
     *
     * @param BaseValidator $subject
     * @param callable      $proceed
     * @param string        $attrCode
     * @param array         $attrParams
     * @param array         $rowData
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function aroundIsAttributeValid(
        BaseValidator $subject,
        callable $proceed,
        $attrCode,
        array $attrParams,
        array $rowData
    ) {
        $result =  $proceed($attrCode, $attrParams, $rowData);

        if ($attrParams['type'] == 'multiselect') {
            $createValuesAllowed = (bool) $this->scopeConfig->getValue(
                Import::CREATE_ATTRIBUTES_CONF_PATH,
                ScopeInterface::SCOPE_STORE
            );
            $attribute = $this->prodAttrFac->create();
            $attribute->load($attrParams['id']);
            $values = explode(Product::PSEUDO_MULTI_LINE_SEPARATOR, $rowData[$attrCode]);
            $valid = true;
            foreach ($values as $value) {
                if ($createValuesAllowed && $attribute->getIsUserDefined()) {
                    $valid = $valid && ($this->string->strlen($value) < Product::DB_MAX_VARCHAR_LENGTH);
                } else {
                    $valid = $valid && isset($attrParams['options'][strtolower($value)]);
                }
            }
            if (!$valid) {
                $this->_addMessages(
                    [
                        sprintf(
                            $subject->context->retrieveMessageTemplate(
                                RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_OPTION
                            ),
                            $attrCode
                        )
                    ]
                );
            }
            return (bool)$valid;
        }

        return $result;
    }
}
