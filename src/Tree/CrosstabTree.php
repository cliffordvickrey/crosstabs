<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tree;

use CliffordVickrey\Crosstabs\Crosstab\CrosstabDataItem;
use CliffordVickrey\Crosstabs\Exception\CrosstabOutOfBoundException;
use CliffordVickrey\Crosstabs\Options\CrosstabVariable;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use Countable;
use Iterator;
use OuterIterator;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

use function array_key_last;
use function array_shift;
use function is_int;
use function is_string;
use function max;

/**
 * @implements OuterIterator<array-key, mixed>
 */
final class CrosstabTree implements Countable, OuterIterator
{
    private const TOTAL = '__TOTAL__';

    /** @var RecursiveIteratorIterator<RecursiveArrayIterator<array-key, mixed>> */
    private RecursiveIteratorIterator $subject;
    /** @var int<0, max> */
    private int $count = 0;
    private ?CrosstabVariable $firstVariableInTree = null;
    private ?CrosstabVariable $lastVariableInTree = null;

    /**
     * @param CrosstabVariableCollection $collection
     * @param non-empty-string $messageTotal
     */
    public function __construct(CrosstabVariableCollection $collection, string $messageTotal = 'Total')
    {
        $treeData = $this->getTreeData($collection->toArray(), $messageTotal, true);
        $arrayIterator = new RecursiveArrayIterator($treeData);
        $this->subject = new RecursiveIteratorIterator($arrayIterator, RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * Represents all variables and categories as a tree. At maximum depth, a placeholder for numeric values is added
     * @param list<CrosstabVariable> $variables
     * @param non-empty-string $messageTotal
     * @param bool $top
     * @return array<array-key, mixed>
     */
    public function getTreeData(
        array $variables,
        string $messageTotal,
        bool $top = false
    ): array {
        $children = $variables;

        $first = 0 === count($children) ? null : array_shift($children);

        if (null === $first) {
            return CrosstabDataItem::__set_state([CrosstabDataItem::FREQUENCY => 0])->toArray();
        }

        if ($top) {
            $this->firstVariableInTree = $first;
        }

        $this->lastVariableInTree = $first;

        // variable node
        $arr = [
            'categories' => [],
            'descendantCount' => max(count($children) - 1, 0),
            'variableDescription' => $first->description,
            'variableName' => $first->name
        ];

        if ($top) {
            $this->count = $arr['descendantCount'];
        }

        $childTree = $this->getTreeData($children, $messageTotal);
        $childCategoryCount = self::getChildCategoryCount($children);
        $siblingCategoryCount = count($first->categories) + 1;

        foreach ($first->categories as $category) {
            // category node
            $arr['categories'][] = [
                'categoryDescription' => $category->description,
                'categoryName' => $category->name,
                'children' => $childTree,
                'descendantCount' => $childCategoryCount,
                'isTotal' => false,
                'siblingCount' => $siblingCategoryCount
            ];
        }

        // total node
        $arr['categories'][] = [
            'categoryDescription' => $messageTotal,
            'categoryName' => self::TOTAL,
            'children' => $childTree,
            'descendantCount' => $childCategoryCount,
            'isTotal' => true,
            'siblingCount' => $siblingCategoryCount
        ];

        if (!$top) {
            return $arr;
        }

        return [$arr];
    }

    /**
     * @param list<CrosstabVariable> $variables
     * @return int<0, max>
     */
    private static function getChildCategoryCount(array $variables): int
    {
        if (0 === count($variables)) {
            return 0;
        }

        $lastKey = array_key_last($variables);

        $count = 0;

        foreach ($variables as $i => $variable) {
            if ($i === $lastKey) {
                continue;
            }

            if ($count < 1) {
                $count = 1;
            }

            $count *= (count($variable->categories) + 1);
        }

        return $count;
    }

    /**
     * @return mixed
     */
    public function current(): mixed
    {
        return $this->subject->current();
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->subject->next();
    }

    /**
     * @return int|string|null
     */
    public function key(): int|string|null
    {
        /** @psalm-suppress MixedAssignment */
        $key = $this->subject->key();

        if (is_int($key) || is_string($key)) {
            return $key;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->subject->valid();
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->subject->rewind();
    }

    /**
     * @return Iterator<array-key, mixed>
     */
    public function getInnerIterator(): Iterator
    {
        return $this->subject->getInnerIterator();
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->subject->getDepth();
    }

    /**
     * @param int|null $level
     * @return RecursiveArrayIterator<array-key, mixed>|null
     */
    public function getSubIterator(?int $level = null): ?RecursiveArrayIterator
    {
        $arr = $this->subject->getSubIterator($level);

        if ($arr instanceof RecursiveArrayIterator) {
            /** @var RecursiveArrayIterator<array-key, mixed> */
            return $arr;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * Returns a pair of the first and last variables in the crosstab (usually, the row and column vars, respectively)
     * @return array{0: CrosstabVariable, 1: CrosstabVariable}
     */
    public function getFirstAndLastVariablesInTree(): array
    {
        if (null === $this->firstVariableInTree) {
            throw new CrosstabOutOfBoundException('No variables are in the tree');
        }

        $lastVariable = $this->lastVariableInTree ?? $this->firstVariableInTree;

        return [$this->firstVariableInTree, $lastVariable];
    }
}
