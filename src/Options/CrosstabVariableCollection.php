<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Options;

use ArrayIterator;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabDataItem;
use CliffordVickrey\Crosstabs\Exception\CrosstabOutOfBoundException;
use CliffordVickrey\Crosstabs\Exception\CrosstabUnexpectedValueException;
use Countable;
use IteratorAggregate;
use Traversable;

use function array_key_first;
use function array_key_last;
use function array_shift;
use function count;
use function is_iterable;
use function is_object;
use function is_string;
use function iterator_to_array;
use function max;
use function sprintf;

/**
 * Represents all variables displayed in the crosstab
 * @implements IteratorAggregate<int, CrosstabVariable>
 */
final readonly class CrosstabVariableCollection implements Countable, IteratorAggregate
{
    private const TOTAL = '__TOTAL__';

    /**
     * @param list<CrosstabVariable> $variables
     */
    public function __construct(private array $variables)
    {
    }

    /**
     * Returns a pair of the first and last variables in the crosstab (usually, the row and column vars, respectively)
     * @return array{0: CrosstabVariable, 1: CrosstabVariable}
     */
    public function getFirstAndLastVariables(): array
    {
        if (0 === count($this->variables)) {
            throw new CrosstabOutOfBoundException('Variables are empty');
        }

        return [
            $this->variables[array_key_first($this->variables)],
            $this->variables[array_key_last($this->variables)]
        ];
    }

    /**
     * Do we have any variables? Do all variables have categories?
     * @return bool
     */
    public function isValid(): bool
    {
        if (0 === count($this->variables)) {
            return false;
        }

        foreach ($this->variables as $variable) {
            if (empty($variable->categories)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return ArrayIterator<positive-int, CrosstabVariable>
     */
    public function getIterator(): ArrayIterator
    {
        /** @var ArrayIterator<positive-int, CrosstabVariable> $it */
        $it = new ArrayIterator($this->variables);
        return $it;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->variables);
    }

    /**
     * Represents all variables and categories as a tree. At maximum depth, a placeholder for numeric values is added
     * @param CrosstabVariableCollection|null $variables
     * @param string|null $messageTotal
     * @return array<array-key, mixed>
     */
    public function toTree(?CrosstabVariableCollection $variables = null, ?string $messageTotal = null): array
    {
        $top = null === $variables;

        $variables = $variables ? $variables->variables : $this->variables;

        $first = 0 === count($variables) ? null : array_shift($variables);

        if (null === $first) {
            return CrosstabDataItem::__set_state([CrosstabDataItem::FREQUENCY => 0])->toArray();
        }

        $children = new self($variables);

        // variable node
        $arr = [
            'categories' => [],
            'descendantCount' => $children->getChildCount(),
            'variableDescription' => $first->description,
            'variableName' => $first->name
        ];

        $childTree = $this->toTree($children, $messageTotal);
        $childCategoryCount = $children->getChildCategoryCount();
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
            'categoryDescription' => $messageTotal ?? 'Total',
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
     * @return list<CrosstabVariable>
     */
    public function toArray(): array
    {
        return $this->variables;
    }

    /**
     * @param array<array-key, mixed> $an_array
     * @return self
     * @psalm-suppress MixedAssignment
     */
    public static function __set_state(array $an_array): self
    {
        $variables = [];

        foreach ($an_array as $rawVariable) {
            $variables[] = self::parseRawVariable($rawVariable);
        }

        return new self($variables);
    }

    /**
     * @param mixed $rawVariable
     * @return CrosstabVariable
     */
    private static function parseRawVariable(mixed $rawVariable): CrosstabVariable
    {
        if ($rawVariable instanceof CrosstabVariable) {
            return clone $rawVariable;
        }

        if (is_string($rawVariable) && '' !== $rawVariable) {
            return new CrosstabVariable($rawVariable);
        }

        if (is_object($rawVariable)) {
            $rawVariable = (array)$rawVariable;
        }

        if (!is_iterable($rawVariable)) {
            throw CrosstabUnexpectedValueException::fromValue(
                $rawVariable,
                sprintf('non-empty-string, object, array, or %s', Traversable::class)
            );
        }

        if ($rawVariable instanceof Traversable) {
            $variableArray = iterator_to_array($rawVariable);
        } else {
            $variableArray = $rawVariable;
        }

        return CrosstabVariable::__set_state($variableArray);
    }

    /**
     * Number of variables descended from this node. Used to calculate colspan of Y axis labels
     * @return int<0, max>
     */
    public function getChildCount(): int
    {
        return max(count($this->variables) - 1, 0);
    }

    /**
     * Number of categories descended from this node. Used to calculate rowspan of Y axis labels
     * @return int<0, max>
     */
    public function getChildCategoryCount(): int
    {
        if (0 === count($this->variables)) {
            return 0;
        }

        $lastKey = array_key_last($this->variables);

        $count = 0;

        foreach ($this->variables as $i => $variable) {
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
}
