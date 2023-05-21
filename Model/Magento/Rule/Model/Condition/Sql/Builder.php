<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\Magento\Rule\Model\Condition\Sql;

use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Rule\Model\Condition\Combine;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Rule\Model\Condition\Sql\ExpressionFactory;

class Builder extends \Magento\Rule\Model\Condition\Sql\Builder
{
    const UNDEFINED_OPERATOR = '<=>';
    const IS_OPERATOR = '==';

    /**
     * @var array
     */
    private $stringConditionOperatorMap = [
        '{}' => ':field LIKE ?',
        '!{}' => ':field NOT LIKE ?',
    ];

    /**
     * @var AbstractCollection|null
     */
    private $collectionCopy;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    public function __construct(
        ExpressionFactory $expressionFactory,
        AttributeRepositoryInterface $attributeRepository
    ) {
        parent::__construct($expressionFactory, $attributeRepository);
        $this->attributeRepository = $attributeRepository;
    }

    public function attachConditionToCollection(
        AbstractCollection $collection,
        Combine $combine
    ): void {
        $this->collectionCopy = $collection;
        parent::attachConditionToCollection($collection, $combine);
        $this->collectionCopy = null;
    }

    /**
     * Fixed issue with filter by default attribute value
     *
     * @param AbstractCondition $condition
     * @param string $value
     * @param bool $isDefaultStoreUsed
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getMappedSqlCondition(
        AbstractCondition $condition,
        string $value = '',
        bool $isDefaultStoreUsed = true
    ): string {
        $argument = $condition->getMappedSqlField();

        // If rule hasn't valid argument - prevent incorrect rule behavior.
        if (empty($argument)) {
            return (string) $this->_expressionFactory->create(['expression' => '1 = -1']);
        } elseif (preg_match('/[^a-z0-9\-_\.\`]/i', $argument) > 0 && !$argument instanceof \Zend_Db_Expr) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid field'));
        }

        if (self::UNDEFINED_OPERATOR === $condition->getOperatorForValidate()) {
            $condition->setOperator(self::IS_OPERATOR);
            $condition->setValue('');
        }

        $conditionOperator = $condition->getOperatorForValidate();

        if (!isset($this->_conditionOperatorMap[$conditionOperator])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Unknown condition operator'));
        }

        $defaultValue = $this->getDefaultValue($this->collectionCopy, $condition->getAttribute());

        //operator 'contains {}' is mapped to 'IN()' query that cannot work with substrings
        // adding mapping to 'LIKE %%'
        if ($condition->getInputType() === 'string'
            && array_key_exists($conditionOperator, $this->stringConditionOperatorMap)
        ) {
            $sql = str_replace(
                ':field',
                (string) $this->_connection->getIfNullSql(
                    $this->_connection->quoteIdentifier($argument),
                    $defaultValue
                ),
                $this->stringConditionOperatorMap[$conditionOperator]
            );
            $bindValue = $condition->getBindArgumentValue();
            $expression = $value . $this->_connection->quoteInto($sql, "%$bindValue%");
        } else {
            $sql = str_replace(
                ':field',
                (string) $this->_connection->getIfNullSql(
                    $this->_connection->quoteIdentifier($argument),
                    $defaultValue
                ),
                $this->_conditionOperatorMap[$conditionOperator]
            );
            $bindValue = $condition->getBindArgumentValue();
            $expression = $value . $this->_connection->quoteInto($sql, $bindValue);
        }
        // values for multiselect attributes can be saved in comma-separated format
        // below is a solution for matching such conditions with selected values
        if (is_array($bindValue) && \in_array($conditionOperator, ['()', '{}'], true)) {
            foreach ($bindValue as $item) {
                $expression .= $this->_connection->quoteInto(
                    " OR (FIND_IN_SET (?, {$this->_connection->quoteIdentifier($argument)}) > 0)",
                    $item
                );
            }
        }

        return (string) $this->_expressionFactory->create(
            ['expression' => $expression]
        );
    }

    /**
     * @param AbstractCollection $collection
     * @param string $attributeCode
     * @return int|string
     */
    private function getDefaultValue(AbstractCollection $collection, string $attributeCode)
    {
        $isDefaultStoreUsed = (int) $collection->getStoreId() === (int) $collection->getDefaultStoreId();

        $defaultValue = 0;

        if (!$isDefaultStoreUsed && $this->isDefaultValueAvaibleForAttribute($attributeCode)) {
            $defaultField = 'at_' . $attributeCode . '_default.value';
            $defaultValue = $this->_connection->quoteIdentifier($defaultField);
        }

        return $defaultValue;
    }

    /**
     * @param string $attributeCode
     * @return bool
     */
    private function isDefaultValueAvaibleForAttribute(string $attributeCode): bool
    {
        try {
            $attribute = $this->attributeRepository->get(Product::ENTITY, $attributeCode);
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return !$attribute->isScopeGlobal();
    }
}
