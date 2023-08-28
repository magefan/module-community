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
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class GetCategoryByProduct implements GetCategoryByProductInterface
{
    /**
     * @var array
     */
    private $productCategory = [];

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * GetModuleVersion constructor.
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface       $storeManager
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Product $product
     * @param null $storeId
     * @return CategoryInterface|null
     * @throws NoSuchEntityException
     */
    public function execute(Product $product, $storeId = null): ?CategoryInterface
    {
        if (!isset($this->productCategory[$product->getId()])) {
            if (is_null($storeId)) {
                $storeId = $this->storeManager->getStore()->getId();
            }

            $categoryIds = $product->getCategoryIds();
            if ($categoryIds) {
                $level = -1;
                $rootCategoryId = $this->storeManager->getStore($storeId)->getRootCategoryId();;

                foreach ($categoryIds as $categoryId) {
                    try {
                        $category = $this->categoryRepository->get($categoryId, $storeId);
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

        if (!isset($this->productCategory[$product->getId()])) {
            $this->productCategory[$product->getId()] = null;
        }

        return $this->productCategory[$product->getId()];
    }
}
