<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\GetCategoryByProductInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Catalog\Api\Data\CategoryInterface;

class GetCategoryByProduct implements GetCategoryByProductInterface
{
    /**
     * @var array
     */
    private $productCategory = [];

    /**
     * @var CategoryRepositoryInterface
     */
    private  $categoryRepository;

    /**
     * GetModuleVersion constructor.
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param Product $product
     * @param StoreInterface $store
     * @return CategoryInterface|null
     * @throws NoSuchEntityException
     */
    public function execute(Product $product, StoreInterface $store): ?CategoryInterface
    {
        if (!isset($this->productCategory[$product->getId()])) {
            $categoryIds = $product->getCategoryIds();
            if ($categoryIds) {
                $level = -1;
                $rootCategoryId = $store->getRootCategoryId();

                foreach ($categoryIds as $categoryId) {
                    try {
                        $category = $this->categoryRepository->get($categoryId, $store->getId());
                        if ($category->getIsActive()
                            && $category->getLevel() > $level
                            && in_array($rootCategoryId, $category->getPathIds())
                        ) {
                            $level = $category->getLevel();
                            $this->productCategory[$product->getId()] = $category;
                        }
                    } catch (\Exception $e) { // phpcs:ignore
                        /* Do nothing */
                    }
                }
            }
        }

        return $this->productCategory[$product->getId()];
    }
}
