<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

declare(strict_types = 1);

namespace Firebear\ImportExport\Model;

use Magento\Catalog\Model\Product;

class ProductUrlRewriteGenerator extends \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator
{

    /**
     * Generate list of urls for global scope
     *
     * @param \Magento\Framework\Data\Collection $productCategories
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite[]
     */
    protected function generateForGlobalScope($productCategories, $product = null, $rootCategoryId = null)
    {
        $urls = [];
        $productId = $this->product->getEntityId();
        foreach ($this->product->getStoreIds() as $id) {
            if (!$this->isGlobalScope($id)
                && !$this->storeViewService->doesEntityHaveOverriddenUrlKeyForStore($id, $productId, Product::ENTITY)
            ) {
                // Default: $urls = array_merge($urls, $this->generateForSpecificStoreView($id, $productCategories));
                // before loading the category collection by looping it, clone it and set the correct store id,
                // so we get the correct url_path & url_key for that specific store id
                $storeSpecificProductCategories = clone $productCategories;
                $storeSpecificProductCategories->setStoreId($id);

                $urls = array_merge($urls, $this->generateForSpecificStoreView($id, $storeSpecificProductCategories));
            }
        }
        return $urls;
    }
}
