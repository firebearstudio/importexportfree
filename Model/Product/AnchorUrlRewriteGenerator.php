<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
declare(strict_types=1);

namespace Firebear\ImportExport\Model\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogUrlRewrite\Model\ObjectRegistry;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;

class AnchorUrlRewriteGenerator extends \Magento\CatalogUrlRewrite\Model\Product\AnchorUrlRewriteGenerator
{

    /** @var ProductUrlPathGenerator */
    protected $urlPathGenerator;

    /** @var UrlRewriteFactory */
    protected $urlRewriteFactory;

    /** @var CategoryRepositoryInterface */
    private $categoryRepository;

    /**
     * @param ProductUrlPathGenerator $urlPathGenerator
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        ProductUrlPathGenerator $urlPathGenerator,
        UrlRewriteFactory $urlRewriteFactory,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->urlPathGenerator = $urlPathGenerator;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Generate list based on categories
     *
     * This method overridden to fix core bug related to error "URL Key for specified store already exists"
     *
     * @param int $storeId
     * @param Product $product
     * @param ObjectRegistry $productCategories
     * @return UrlRewrite[]
     */
    public function generate($storeId, Product $product, ObjectRegistry $productCategories)
    {
        $urls = [];

        // HE-Paul: Keep track of the $storeId passed in to this method
        $currentStore = $storeId;
        foreach ($productCategories->getList() as $category) {
            $anchorCategoryIds = $category->getAnchorsAbove();
            if ($anchorCategoryIds) {
                foreach ($anchorCategoryIds as $anchorCategoryId) {
                    // Default: $anchorCategory = $this->categoryRepository->get($anchorCategoryId);
                    // Here check if the anchorCategory is the Root Catalog and set appropriately
                    $storeId = ($anchorCategoryId === "1" ? "0" : $currentStore);

                    $anchorCategory = $this->categoryRepository->get($anchorCategoryId, $storeId);
                    $urls[] = $this->urlRewriteFactory->create()
                        ->setEntityType(ProductUrlRewriteGenerator::ENTITY_TYPE)
                        ->setEntityId($product->getId())
                        ->setRequestPath(
                            $this->urlPathGenerator->getUrlPathWithSuffix(
                                $product,
                                $storeId,
                                $anchorCategory
                            )
                        )
                        ->setTargetPath(
                            $this->urlPathGenerator->getCanonicalUrlPath(
                                $product,
                                $anchorCategory
                            )
                        )
                        ->setStoreId($storeId)
                        ->setMetadata(['category_id' => $anchorCategory->getId()]);
                }
            }
        }
        return $urls;
    }
}
