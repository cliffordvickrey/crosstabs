<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Helper;

use CliffordVickrey\Crosstabs\Crosstab\CrosstabDataItem;
use CliffordVickrey\Crosstabs\Exception\CrosstabInvalidArgumentException;
use CliffordVickrey\Crosstabs\Options\CrosstabPercentType;
use CliffordVickrey\Crosstabs\Options\CrosstabVariable;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use CliffordVickrey\Crosstabs\Tree\CrosstabTree;
use CliffordVickrey\Crosstabs\Utilities\CrosstabExtractionUtilities;
use CliffordVickrey\Crosstabs\Utilities\CrosstabMathUtilities;
use RecursiveArrayIterator;
use WeakMap;

use function array_diff_key;
use function array_filter;
use function array_intersect_key;
use function array_pop;
use function http_build_query;
use function is_array;

/**
 * @internal
 */
final class CrosstabTreeBuilder implements CrosstabTreeBuilderInterface
{
    /** @var WeakMap<CrosstabTree, list<list<CrosstabDataItem>>> */
    private WeakMap $treeToMatrixMap;

    /**
     *
     */
    public function __construct()
    {
        /** @psalm-suppress PropertyTypeCoercion */
        $this->treeToMatrixMap = new WeakMap();
    }

    /**
     * @inheritDoc
     */
    public function buildTree(
        CrosstabVariableCollection $variables,
        array $totals,
        CrosstabPercentType $percentType = CrosstabPercentType::TOTAL,
        string $messageTotal = 'Total',
        int $scale = CrosstabMathUtilities::DEFAULT_SCALE
    ): CrosstabTree {
        $matrix = [];
        $counter = 0;
        $y = -1;

        $tree = new CrosstabTree($variables, $messageTotal);

        $rowAndColVars = $this->getRowAndColVariables($variables);
        $colVar = $rowAndColVars['col'];
        $rowVar = $rowAndColVars['row'];

        $cols = count($colVar->categories);

        $query = [];
        $currentVariable = '';

        $totalN = $totals['n'][''] ?? 0.0;
        $totalWeightedN = $totals['weightedN'][''] ?? 0.0;

        $variablesByDepth = [];

        foreach ($tree as $node) {
            if (!is_array($node)) {
                continue;
            }

            $depth = $tree->getDepth();

            if (!isset($variablesByDepth[$depth])) {
                $variablesByDepth[$depth] = $currentVariable;
            } else {
                $currentVariable = $variablesByDepth[$depth];
            }

            if (isset($node['variableName'])) {
                $currentVariable = CrosstabExtractionUtilities::extractString('variableName', $node);
                $variablesByDepth[$depth] = CrosstabExtractionUtilities::extractString('variableName', $node);
                continue;
            }

            if (isset($node['categoryName'])) {
                $category = CrosstabExtractionUtilities::extractString('categoryName', $node);
                $isTotal = (bool)($node['isTotal'] ?? false);
                $query[$currentVariable] = $isTotal ? null : $category;
                continue;
            }

            if (!isset($node[CrosstabDataItem::FREQUENCY])) {
                continue;
            }

            $key = http_build_query($query);
            $n = $totals['n'][$key] ?? 0.0;

            $weightedN = $totals['weightedN'][$key] ?? 0.0;

            $rowSubTotalKey = http_build_query(array_diff_key($query, [$colVar->name => true]));
            $rowSubTotalN = $totals['n'][$rowSubTotalKey] ?? 0.0;
            $rowSubTotalWeightedN = $totals['weightedN'][$rowSubTotalKey] ?? 0.0;

            $colSubTotalKey = http_build_query(array_intersect_key($query, [$colVar->name => true]));
            $colSubTotalN = $totals['n'][$colSubTotalKey] ?? 0.0;
            $colSubTotalWeightedN = $totals['weightedN'][$colSubTotalKey] ?? 0.0;

            $expectedN = CrosstabMathUtilities::multiply(
                $colSubTotalN,
                (float)CrosstabMathUtilities::divide(
                    $rowSubTotalN,
                    $totalN,
                    $scale
                ),
                $scale
            );

            $expectedWeightedN = CrosstabMathUtilities::multiply(
                $colSubTotalWeightedN,
                (float)CrosstabMathUtilities::divide(
                    $rowSubTotalWeightedN,
                    $totalWeightedN,
                    $scale
                ),
                $scale
            );

            $percentDivisors = $this->getPercentDivisors($percentType, $totals, $rowVar, $colVar, $query);

            /** @var RecursiveArrayIterator<array-key, mixed> $subIterator */
            $subIterator = $tree->getSubIterator();

            $item = CrosstabDataItem::__set_state([
                CrosstabDataItem::EXPECTED_FREQUENCY => $expectedN,
                CrosstabDataItem::EXPECTED_PERCENT => CrosstabMathUtilities::divide(
                    $expectedN,
                    $percentDivisors['unweighted'],
                    $scale
                ),
                CrosstabDataItem::FREQUENCY => $n,
                CrosstabDataItem::IS_TOTAL => count(array_filter($query, is_null(...))) > 0,
                CrosstabDataItem::PARAMS => $key,
                CrosstabDataItem::PERCENT => CrosstabMathUtilities::divide(
                    $n,
                    $percentDivisors['unweighted'],
                    $scale
                ),
                CrosstabDataItem::WEIGHTED_EXPECTED_FREQUENCY => $expectedWeightedN,
                CrosstabDataItem::WEIGHTED_EXPECTED_PERCENT => CrosstabMathUtilities::divide(
                    $expectedWeightedN,
                    $percentDivisors['weighted'],
                    $scale
                ),
                CrosstabDataItem::WEIGHTED_FREQUENCY => $weightedN,
                CrosstabDataItem::WEIGHTED_PERCENT => CrosstabMathUtilities::divide(
                    $weightedN,
                    $percentDivisors['weighted'],
                    $scale
                ),
            ]);

            $subIterator->offsetSet('children', $item->toArray());

            if ($item->isTotal) {
                continue;
            }

            if (0 === $counter % $cols) {
                $y++;
            }

            if (!isset($matrix[$y])) {
                $matrix[$y] = [];
            }

            $matrix[$y][] = $item;

            $counter++;
        }

        /** @psalm-suppress ArgumentTypeCoercion It's always a list of lists of objects */
        $this->treeToMatrixMap[$tree] = $matrix;

        return $tree;
    }

    /**
     * @param CrosstabVariableCollection $variables
     * @return array{row: CrosstabVariable, col: CrosstabVariable}
     */
    private function getRowAndColVariables(CrosstabVariableCollection $variables): array
    {
        $vars = $variables->toArray();

        if (0 === count($vars)) {
            throw new CrosstabInvalidArgumentException('Variable collection cannot be empty');
        }

        $colVar = array_pop($vars);
        $rowVar = $colVar;

        if (0 !== count($vars)) {
            $rowVar = array_pop($vars);
        }

        return ['row' => $rowVar, 'col' => $colVar];
    }

    /**
     * @param CrosstabPercentType $percentType
     * @param array{n: array<string, float>, weightedN: array<string, float>} $totals
     * @param CrosstabVariable $rowVar
     * @param CrosstabVariable $colVar
     * @param array<string, string|null> $query
     * @return array{unweighted: float, weighted: float}
     */
    private function getPercentDivisors(
        CrosstabPercentType $percentType,
        array $totals,
        CrosstabVariable $rowVar,
        CrosstabVariable $colVar,
        array $query
    ): array {
        $key = match ($percentType) {
            CrosstabPercentType::COLUMN => http_build_query(array_intersect_key($query, [$colVar->name => true])),
            CrosstabPercentType::COLUMN_WITHIN_LAYER => http_build_query(array_diff_key($query, [
                $rowVar->name => true
            ])),
            CrosstabPercentType::ROW => http_build_query(array_diff_key($query, [$colVar->name => true])),
            CrosstabPercentType::TOTAL_WITHIN_LAYER => http_build_query(array_diff_key($query, [
                $rowVar->name => true,
                $colVar->name => true
            ])),
            default => ''
        };

        return [
            'unweighted' => $totals['n'][$key] ?? 0.0,
            'weighted' => $totals['weightedN'][$key] ?? 0.0
        ];
    }

    /**
     * @inheritDoc
     */
    public function getMatrix(CrosstabTree $tree): array
    {
        return $this->treeToMatrixMap[$tree] ?? [];
    }
}
