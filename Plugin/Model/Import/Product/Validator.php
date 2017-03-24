<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Firebear\ImportExport\Plugin\Model\Import\Product;

use Firebear\ImportExport\Plugin\Model\Import;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Validator\AbstractValidator;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Validator
 * Rewrite this class to allow import attribute values on the fly.
 */
class Validator extends Product\Validator
{
    protected $scopeConfig;

    protected $prodAttrFac;

    /**
     * @param StringUtils             $string
     * @param RowValidatorInterface[] $validators
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
     * @param string $attrCode
     * @param array  $attrParams
     * @param array  $rowData
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function isAttributeValid($attrCode, array $attrParams, array $rowData)
    {
        $this->_rowData = $rowData;
        if(isset($rowData['product_type']) && !empty($attrParams['apply_to'])
           && !in_array($rowData['product_type'], $attrParams['apply_to'])
        ) {
            return true;
        }
        if(!$this->isRequiredAttributeValid($attrCode, $attrParams, $rowData)) {
            $valid = false;
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(
                            RowValidatorInterface::ERROR_VALUE_IS_REQUIRED
                        ),
                        $attrCode
                    ),
                ]
            );

            return $valid;
        }
        if(!strlen(trim($rowData[$attrCode]))) {
            return true;
        }
        switch($attrParams['type']) {
            case 'varchar':
            case 'text':
                $valid = $this->textValidation($attrCode, $attrParams['type']);
                break;
            case 'decimal':
            case 'int':
                $valid = $this->numericValidation($attrCode, $attrParams['type']);
                break;
            case 'select':
            case 'boolean':
            case 'multiselect':
                $createValuesAllowed = (bool) $this->scopeConfig->getValue(
                    Import::CREATE_ATTRIBUTES_CONF_PATH,
                    ScopeInterface::SCOPE_STORE
                );
                $attribute = $this->prodAttrFac->create();
                $attribute->load($attrParams['id']);
                $values = explode(Product::PSEUDO_MULTI_LINE_SEPARATOR, $rowData[$attrCode]);
                $valid = true;
                foreach($values as $value) {
                    if($createValuesAllowed && $attribute->getIsUserDefined()) {
                        $valid = $valid && ($this->string->strlen($value) < Product::DB_MAX_VARCHAR_LENGTH);
                    } else {
                        $valid = $valid && isset($attrParams['options'][strtolower($value)]);
                    }
                }
                if(!$valid) {
                    $this->_addMessages(
                        [
                            sprintf(
                                $this->context->retrieveMessageTemplate(
                                    RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_OPTION
                                ),
                                $attrCode
                            ),
                        ]
                    );
                }
                break;
            case 'datetime':
                $val = trim($rowData[$attrCode]);
                $valid = strtotime($val) !== false;
                if(!$valid) {
                    $this->_addMessages([RowValidatorInterface::ERROR_INVALID_ATTRIBUTE_TYPE]);
                }
                break;
            default:
                $valid = true;
                break;
        }
        if($valid && !empty($attrParams['is_unique'])) {
            if(isset($this->_uniqueAttributes[$attrCode][$rowData[$attrCode]])
               && ($this->_uniqueAttributes[$attrCode][$rowData[$attrCode]] != $rowData[Product::COL_SKU])
            ) {
                $this->_addMessages([RowValidatorInterface::ERROR_DUPLICATE_UNIQUE_ATTRIBUTE]);

                return false;
            }
            $this->_uniqueAttributes[$attrCode][$rowData[$attrCode]] = $rowData[Product::COL_SKU];
        }

        return (bool) $valid;
    }
}
