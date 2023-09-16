<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs;

use CliffordVickrey\Crosstabs\Crosstab\Crosstab;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabCell;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabDataItem;
use CliffordVickrey\Crosstabs\Exception\CrosstabInvalidArgumentException;
use CliffordVickrey\Crosstabs\Exception\CrosstabLogicException;
use CliffordVickrey\Crosstabs\NumberFormatting\CrosstabNumberFormatterFlyweight;
use CliffordVickrey\Crosstabs\NumberFormatting\CrosstabNumberFormatterFlyweightInterface;
use CliffordVickrey\Crosstabs\NumberFormatting\CrosstabNumberFormatterType;
use CliffordVickrey\Crosstabs\Options\CrosstabCategory;
use CliffordVickrey\Crosstabs\Options\CrosstabOptions;
use CliffordVickrey\Crosstabs\Options\CrosstabPercentType;
use CliffordVickrey\Crosstabs\Options\CrosstabVariable;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use CliffordVickrey\Crosstabs\SourceData\CrosstabSourceDataCollection;
use CliffordVickrey\Crosstabs\Utilities\CrosstabCastingUtilities;
use CliffordVickrey\Crosstabs\Utilities\CrosstabExtractionUtilities;
use CliffordVickrey\Crosstabs\Utilities\CrosstabMathUtilities;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

use function array_diff_key;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_key_last;
use function array_keys;
use function array_merge;
use function array_reduce;
use function array_values;
use function count;
use function http_build_query;
use function in_array;
use function is_array;
use function is_int;
use function max;
use function strnatcasecmp;
use function usort;

class CrosstabBuilder extends CrosstabOptions implements CrosstabBuilderInterface
{
    private const MATRIX_KEY = '__matrix';

    private readonly CrosstabNumberFormatterFlyweightInterface $formatterFlyweight;

    /**
     * @param CrosstabNumberFormatterFlyweightInterface|null $formatterFlyweight
     */
    public function __construct(?CrosstabNumberFormatterFlyweightInterface $formatterFlyweight = null)
    {
        if (null === $formatterFlyweight) {
            $formatterFlyweight = new CrosstabNumberFormatterFlyweight();
        }

        $this->formatterFlyweight = $formatterFlyweight;
    }

    /**
     * @inheritDoc
     */
    public function build(): Crosstab
    {
        $this->assertValidOptions();

        $sourceData = CrosstabSourceDataCollection::fromRawData($this->rawData, $this->keyN, $this->keyWeightedN);

        if (0 === count($sourceData)) {
            return $this->buildEmptyCrossTab();
        }

        $variables = $this->collectVariables($sourceData);

        if (!$variables->isValid()) {
            return $this->buildEmptyCrossTab();
        }

        $totals = $this->tabulate($variables, $sourceData);

        $treeIterator = $this->buildTree($variables, $totals);

        if (count($variables) < 2) {
            return $this->buildSimple($variables, $treeIterator);
        }

        return $this->buildComplex($variables, $treeIterator);
    }

    /**
     * Throws exception if builder is in an invalid state
     * @return void
     */
    private function assertValidOptions(): void
    {
        if (null === $this->rowVariableName) {
            throw new CrosstabInvalidArgumentException('No row variable provided');
        }

        if (0 === count($this->getDataHeaders())) {
            throw new CrosstabInvalidArgumentException('No frequency or percent to tabulate');
        }
    }

    /**
     * Gets the data headers (frequency, percentage, etc.) that will appear in the table
     * @return array<string, string>
     */
    private function getDataHeaders(): array
    {
        $headers = [
            CrosstabDataItem::FREQUENCY => $this->messageFrequency,
            CrosstabDataItem::PERCENT => $this->messagePercent,
            CrosstabDataItem::EXPECTED_FREQUENCY => $this->messageExpectedFrequency,
            CrosstabDataItem::EXPECTED_PERCENT => $this->messageExpectedPercent,
            CrosstabDataItem::WEIGHTED_PERCENT => $this->messageWeightedPercent,
            CrosstabDataItem::WEIGHTED_FREQUENCY => $this->messageWeightedFrequency,
            CrosstabDataItem::WEIGHTED_EXPECTED_FREQUENCY => $this->messageWeightedExpectedFrequency,
            CrosstabDataItem::WEIGHTED_EXPECTED_PERCENT => $this->messageWeightedExpectedPercent
        ];

        $headersToShow = array_filter([
            CrosstabDataItem::EXPECTED_FREQUENCY => $this->showExpectedFrequency,
            CrosstabDataItem::EXPECTED_PERCENT => $this->showExpectedPercent,
            CrosstabDataItem::FREQUENCY => $this->showFrequency,
            CrosstabDataItem::PERCENT => $this->showPercent,
            CrosstabDataItem::WEIGHTED_EXPECTED_FREQUENCY => $this->showWeightedExpectedFrequency,
            CrosstabDataItem::WEIGHTED_EXPECTED_PERCENT => $this->showWeightedExpectedPercent,
            CrosstabDataItem::WEIGHTED_PERCENT => $this->showWeightedPercent,
            CrosstabDataItem::WEIGHTED_FREQUENCY => $this->showWeightedFrequency
        ]);

        return array_intersect_key($headers, $headersToShow);
    }

    /**
     * No data? Return an empty table
     * @return Crosstab
     */
    private function buildEmptyCrossTab(): Crosstab
    {
        return Crosstab::withoutData($this->messageNoData, $this->title);
    }

    /**
     * Gets a collection of layer, row, and column variables, each with a complete list of categories that belong to the
     * dataset
     * @param CrosstabSourceDataCollection $sourceData
     * @return CrosstabVariableCollection
     */
    private function collectVariables(CrosstabSourceDataCollection $sourceData): CrosstabVariableCollection
    {
        $parsedVariables = [];

        $variables = array_values(array_filter([$this->buildRowVariable(), $this->buildColVariable()]));

        foreach ($this->layers as $layer) {
            $variables[] = $layer;
        }

        foreach ($variables as $variable) {
            $sort = empty($variable->categories);

            $categoriesIndexedByName = array_reduce(
                $variable->categories,
                static fn(array $carry, CrosstabCategory $category) => array_merge(
                    $carry,
                    [$category->name => $category]
                ),
                []
            );

            $nonEmptyCategoriesIndexedByName = [];

            foreach ($sourceData as $row) {
                $value = CrosstabCastingUtilities::toString($row->getValue($variable->name));

                if ('' === $value) {
                    continue;
                }

                $nonEmptyCategoriesIndexedByName[$value] = true;

                if (isset($categoriesIndexedByName[$value])) {
                    continue;
                }

                $categoriesIndexedByName[$value] = new CrosstabCategory($value);
            }

            /** @var list<CrosstabCategory> $categories */
            $categories = array_values(array_intersect_key($categoriesIndexedByName, $nonEmptyCategoriesIndexedByName));

            if ($sort) {
                usort($categories, static function (CrosstabCategory $a, CrosstabCategory $b) {
                    $compA = strnatcasecmp($a->description, $b->description);

                    if (0 !== $compA) {
                        return $compA;
                    }

                    return strnatcasecmp($a->name, $b->name);
                });
            }

            $parsedVariables[] = new CrosstabVariable($variable->name, $variable->description, $categories);
        }

        return CrosstabVariableCollection::__set_state($parsedVariables)->inLeftToRightOrder();
    }

    /**
     * Encapsulates the row variable as an object
     * @return CrosstabVariable
     */
    private function buildRowVariable(): CrosstabVariable
    {
        return CrosstabVariable::__set_state([
            'name' => (string)$this->rowVariableName,
            'description' => $this->rowVariableDescription,
            'categories' => $this->rowVariableCategories
        ]);
    }

    /**
     * Encapsulates the column variable as an object
     * @return CrosstabVariable|null
     */
    private function buildColVariable(): ?CrosstabVariable
    {
        if (null === $this->colVariableName) {
            return null;
        }

        return CrosstabVariable::__set_state([
            'name' => $this->colVariableName,
            'description' => $this->colVariableDescription,
            'categories' => $this->colVariableCategories
        ]);
    }

    /**
     * Tabulates the frequency and weighted frequency of every possible combination of variable categories
     * @param CrosstabVariableCollection $variables
     * @param CrosstabSourceDataCollection $sourceData
     * @return array{n: array<string, float>, weightedN: array<string, float>}
     */
    private function tabulate(CrosstabVariableCollection $variables, CrosstabSourceDataCollection $sourceData): array
    {
        if (0 === count($variables)) {
            throw new CrosstabLogicException('Variables cannot be empty');
        }

        $totals = ['n' => [], 'weightedN' => []];

        foreach ($sourceData as $sourceRow) {
            $fullQuery = [];
            $elements = [];

            foreach ($variables as $variable) {
                $value = CrosstabCastingUtilities::toString($sourceRow->getValue($variable->name));

                if ('' === $value) {
                    continue 2;
                }

                $fullQuery[$variable->name] = $value;
                $elements[] = $variable->name;
            }

            $powerSet = CrosstabMathUtilities::getPowerSet($elements);

            foreach ($powerSet as $elementsInPowerSet) {
                $query = array_intersect_key($fullQuery, array_flip($elementsInPowerSet));

                $key = http_build_query($query);

                if (!isset($totals['n'][$key])) {
                    $totals['n'][$key] = (float)$sourceRow->n;
                } else {
                    $totals['n'][$key] = CrosstabMathUtilities::add(
                        $totals['n'][$key],
                        (float)$sourceRow->n,
                        $this->mathematicalScale
                    );
                }

                if (!isset($totals['weightedN'][$key])) {
                    $totals['weightedN'][$key] = (float)$sourceRow->weightedN;
                } else {
                    $totals['weightedN'][$key] = CrosstabMathUtilities::add(
                        $totals['weightedN'][$key],
                        (float)$sourceRow->weightedN,
                        $this->mathematicalScale
                    );
                }
            }
        }

        return $totals;
    }

    /**
     * Recursively iterates through the variable tree and populates it with data
     * @param CrosstabVariableCollection $variables
     * @param array{n: array<string, float>, weightedN: array<string, float>} $totals
     * @return RecursiveIteratorIterator<RecursiveArrayIterator<array-key, mixed>>
     * @psalm-suppress UnnecessaryVarAnnotation
     */
    private function buildTree(CrosstabVariableCollection $variables, array $totals): RecursiveIteratorIterator
    {
        $matrix = [];
        $counter = 0;
        $y = -1;

        /** @var RecursiveArrayIterator<array-key, mixed> $arrayIterator */
        $arrayIterator = new RecursiveArrayIterator($variables->toTree(messageTotal: $this->messageTotal));
        $treeIterator = new RecursiveIteratorIterator($arrayIterator, RecursiveIteratorIterator::SELF_FIRST);

        $lastVariableObj = $variables->getFirstAndLastVariables()[1];
        $lastVariable = $lastVariableObj->name;
        $cols = count($lastVariableObj->categories);

        $query = [];
        $currentVariable = '';

        $totalN = $totals['n'][''] ?? 0.0;
        $totalWeightedN = $totals['weightedN'][''] ?? 0.0;

        $variablesByDepth = [];

        foreach ($treeIterator as $node) {
            if (!is_array($node)) {
                continue;
            }

            $depth = $treeIterator->getDepth();

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

            $rowSubTotalKey = http_build_query(array_diff_key($query, [$lastVariable => true]));
            $rowSubTotalN = $totals['n'][$rowSubTotalKey] ?? 0.0;
            $rowSubTotalWeightedN = $totals['weightedN'][$rowSubTotalKey] ?? 0.0;

            $colSubTotalKey = http_build_query(array_intersect_key($query, [$lastVariable => true]));
            $colSubTotalN = $totals['n'][$colSubTotalKey] ?? 0.0;
            $colSubTotalWeightedN = $totals['weightedN'][$colSubTotalKey] ?? 0.0;

            $expectedN = CrosstabMathUtilities::multiply(
                $colSubTotalN,
                (float)CrosstabMathUtilities::divide(
                    $rowSubTotalN,
                    $totalN,
                    $this->mathematicalScale
                ),
                $this->mathematicalScale
            );

            $expectedWeightedN = CrosstabMathUtilities::multiply(
                $colSubTotalWeightedN,
                (float)CrosstabMathUtilities::divide(
                    $rowSubTotalWeightedN,
                    $totalWeightedN,
                    $this->mathematicalScale
                ),
                $this->mathematicalScale
            );

            switch ($this->percentType) {
                case CrosstabPercentType::COLUMN:
                    $percentDivisor = $colSubTotalN;
                    $percentDivisorWeighted = $colSubTotalWeightedN;
                    break;
                case CrosstabPercentType::COLUMN_WITHIN_LAYER:
                    $totalColWithinLayerKey = http_build_query(array_diff_key($query, [
                        (string)$this->rowVariableName => true
                    ]));
                    $percentDivisor = $totals['n'][$totalColWithinLayerKey] ?? 0.0;
                    $percentDivisorWeighted = $totals['weightedN'][$totalColWithinLayerKey] ?? 0.0;
                    break;
                case CrosstabPercentType::ROW:
                    $percentDivisor = $rowSubTotalN;
                    $percentDivisorWeighted = $rowSubTotalWeightedN;
                    break;
                case CrosstabPercentType::TOTAL_WITHIN_LAYER:
                    $totalWithinLayerKey = http_build_query(array_diff_key($query, [
                        (string)$this->rowVariableName => true,
                        (string)$this->colVariableName => true
                    ]));
                    $percentDivisor = $totals['n'][$totalWithinLayerKey] ?? 0.0;
                    $percentDivisorWeighted = $totals['weightedN'][$totalWithinLayerKey] ?? 0.0;
                    break;
                default:
                    $percentDivisor = $totalN;
                    $percentDivisorWeighted = $totalWeightedN;
            }

            /** @var RecursiveArrayIterator<array-key, mixed> $subIterator */
            $subIterator = $treeIterator->getSubIterator();

            $item = CrosstabDataItem::__set_state([
                CrosstabDataItem::EXPECTED_FREQUENCY => $expectedN,
                CrosstabDataItem::EXPECTED_PERCENT => CrosstabMathUtilities::divide(
                    $expectedN,
                    $percentDivisor,
                    $this->mathematicalScale
                ),
                CrosstabDataItem::FREQUENCY => $n,
                CrosstabDataItem::IS_TOTAL => count(array_filter($query, is_null(...))) > 0,
                CrosstabDataItem::PARAMS => $key,
                CrosstabDataItem::PERCENT => CrosstabMathUtilities::divide(
                    $n,
                    $percentDivisor,
                    $this->mathematicalScale
                ),
                CrosstabDataItem::WEIGHTED_EXPECTED_FREQUENCY => $expectedWeightedN,
                CrosstabDataItem::WEIGHTED_EXPECTED_PERCENT => CrosstabMathUtilities::divide(
                    $expectedWeightedN,
                    $percentDivisorWeighted,
                    $this->mathematicalScale
                ),
                CrosstabDataItem::WEIGHTED_FREQUENCY => $weightedN,
                CrosstabDataItem::WEIGHTED_PERCENT => CrosstabMathUtilities::divide(
                    $weightedN,
                    $percentDivisorWeighted,
                    $this->mathematicalScale
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

        // hack: store the rectangular matrix of data items at the top of the tree
        /** @var RecursiveArrayIterator<array-key, mixed> $subIterator */
        $subIterator = $treeIterator->getSubIterator(0);
        $subIterator->offsetSet(self::MATRIX_KEY, $matrix);
        return $treeIterator;
    }

    /**
     * Builds a simple frequency distribution with one variable
     * @param CrosstabVariableCollection $variables
     * @param RecursiveIteratorIterator<RecursiveArrayIterator<array-key, mixed>> $treeIterator
     * @return Crosstab
     */
    private function buildSimple(
        CrosstabVariableCollection $variables,
        RecursiveIteratorIterator $treeIterator
    ): Crosstab {
        list ($firstVariable) = $variables->getFirstAndLastVariables();

        $lastCategoryKey = array_key_last($firstVariable->categories);
        $lastCategory = '';

        if (is_int($lastCategoryKey)) {
            $lastCategory = $firstVariable->categories[$lastCategoryKey]->name;
        }

        $dataHeaders = $this->getDataHeaders();
        $dataHeaderKeys = array_keys($dataHeaders);
        /** @var positive-int $dataHeaderCount */
        $dataHeaderCount = count($dataHeaders);
        $tableWidth = $dataHeaderCount + 1;

        $rows = [];

        if (null !== $this->title) {
            $rows[] = [CrosstabCell::header($this->title, $tableWidth, attributes: [
                'class' => CrosstabCell::APPEARANCE_TITLE
            ])];
        }

        $axisRow = [
            CrosstabCell::header($firstVariable->description, attributes: ['class' => CrosstabCell::APPEARANCE_Y_AXIS])
        ];

        foreach ($dataHeaders as $dataHeader) {
            $axisRow[] = CrosstabCell::header($dataHeader, attributes: [
                'class' => CrosstabCell::APPEARANCE_X_AXIS_CATEGORY_LABEL
            ]);
        }

        $rows[] = $axisRow;

        $isLastCategory = false;
        $rowIndex = count($rows) - 1;

        /** @psalm-suppress MixedAssignment */
        foreach ($treeIterator as $key => $node) {
            if ($key === self::MATRIX_KEY) {
                break;
            }

            if (!is_array($node)) {
                continue;
            }

            if (isset($node['categoryDescription'])) {
                $categoryName = CrosstabExtractionUtilities::extractString('categoryName', $node);

                $categoryDescription = CrosstabExtractionUtilities::extractString('categoryDescription', $node);

                $attr = ['class' => CrosstabCell::APPEARANCE_Y_AXIS_CATEGORY_LABEL_SIMPLE];

                $isTotal = (bool)($node['isTotal'] ?? false);

                if ($isTotal) {
                    $attr['class'] .= ' ' . CrosstabCell::APPEARANCE_TOTAL_LABEL;
                }

                $isLastCategory = $categoryName === $lastCategory;

                if ($isLastCategory) {
                    $attr['class'] .= ' ' . CrosstabCell::APPEARANCE_BOTTOM_CELL;
                }

                $rowIndex++;
                $rows[] = [CrosstabCell::header($categoryDescription, attributes: $attr)];
                continue;
            }

            if (!array_key_exists(CrosstabDataItem::FREQUENCY, $node)) {
                continue;
            }

            foreach ($dataHeaderKeys as $dataType) {
                $percent = self::isDataTypePercent($dataType);

                $value = CrosstabExtractionUtilities::extractNumeric($dataType, $node);

                $attr = ['class' => CrosstabCell::APPEARANCE_CELL];

                if ($isLastCategory) {
                    $attr['class'] .= ' ' . CrosstabCell::APPEARANCE_BOTTOM_CELL;
                }

                if ($node[CrosstabDataItem::IS_TOTAL] ?? false) {
                    $attr['class'] .= ' ' . CrosstabCell::APPEARANCE_TOTAL;
                }

                $dataCell = CrosstabCell::dataCell($this->formatNumber($value, $percent), $value, attributes: $attr);

                $rows[$rowIndex][] = $dataCell;
            }
        }

        return new Crosstab($rows, self::getMatrix($treeIterator));
    }

    /**
     * @param string $dataType
     * @return bool
     */
    private static function isDataTypePercent(string $dataType): bool
    {
        return in_array($dataType, [
            CrosstabDataItem::EXPECTED_PERCENT,
            CrosstabDataItem::PERCENT,
            CrosstabDataItem::WEIGHTED_EXPECTED_PERCENT,
            CrosstabDataItem::WEIGHTED_PERCENT
        ]);
    }

    /**
     * Formats a number in various ways
     * @param mixed $valueParsed
     * @param bool $percent
     * @return string
     */
    private function formatNumber(mixed $valueParsed, bool $percent): string
    {
        $valueParsed = CrosstabCastingUtilities::toNumeric($valueParsed);

        if (null === $valueParsed) {
            return $this->messageNil; // NULL values (percentages of zero, etc.) get a special label
        }

        $valueParsed = (float)$valueParsed;

        $type = $percent ? CrosstabNumberFormatterType::PERCENT : CrosstabNumberFormatterType::DECIMAL;

        if ($percent) {
            $scale = $this->scalePercent;
        } elseif (CrosstabMathUtilities::isWholeNumber($valueParsed)) {
            $scale = 0;
        } else {
            $scale = $this->scaleDecimal;
        }

        return (string)$this->formatterFlyweight
            ->getNumberFormatter($type, $this->locale, $scale)
            ->format($valueParsed);
    }

    /**
     * Gets the contingency table matrix as the top of the tree
     * @param RecursiveIteratorIterator<RecursiveArrayIterator<array-key, mixed>> $treeIterator
     * @return list<list<CrosstabDataItem>>
     */
    private static function getMatrix(RecursiveIteratorIterator $treeIterator): array
    {
        $treeIterator->rewind();
        /** @var RecursiveArrayIterator<array-key, mixed> $subIterator */
        $subIterator = $treeIterator->getSubIterator(0);
        /** @var list<list<CrosstabDataItem>> $matrix */
        $matrix = $subIterator->offsetGet(self::MATRIX_KEY);
        return $matrix;
    }

    /**
     * Builds a crosstab or layered crosstab
     * @param CrosstabVariableCollection $variables
     * @param RecursiveIteratorIterator<RecursiveArrayIterator<array-key, mixed>> $treeIterator
     * @return Crosstab
     */
    private function buildComplex(
        CrosstabVariableCollection $variables,
        RecursiveIteratorIterator $treeIterator
    ): Crosstab {
        list($firstVariable, $lastVariable) = $variables->getFirstAndLastVariables();

        // a whole lot of ugly...
        $colCategoryCount = count($lastVariable->categories) + 1;
        $colCounter = 0; // used to keep track of the row to which we're writing data cells
        $currentVariableDepth = 0;
        $currentVariableNode = [];
        $dataHeaders = $this->getDataHeaders();
        $dataHeaderKeys = array_keys($dataHeaders);
        /** @var positive-int $dataHeaderCount */
        $dataHeaderCount = count($dataHeaders);
        /** @var array<int, int> $depthToRowIndexMap */
        $depthToRowIndexMap = [];
        $lastDataType = array_key_last($dataHeaders);
        $variableDepthByDepth = [];
        $variableFlushed = false;
        $variableNodeByDepth = [];

        $rows = $this->buildCrosstabHeader(
            $firstVariable,
            $lastVariable,
            count($lastVariable->categories) + 1,
            self::getYAxisWidth($treeIterator)
        );

        $topRowIndex = count($rows);

        /**
         * Closure for writing a cell to the table. Keeps track of the current row index for each column
         * @param CrosstabCell $cell
         * @param int $depth
         * @return void
         */
        $write = function (CrosstabCell $cell, int $depth) use (&$depthToRowIndexMap, &$rows, $topRowIndex): void {
            /** @var array<int, int> $depthToRowIndexMap */
            $rowIndex = $depthToRowIndexMap[$depth] ?? null;

            if (null === $rowIndex) {
                $rowIndex = $topRowIndex;
                $depthToRowIndexMap[$depth] = $rowIndex;
            }

            /** @var array<int, array<int, CrosstabCell>> $rows */
            if (!isset($rows[$rowIndex])) {
                $rows[$rowIndex] = [];
            }

            $rows[$rowIndex][] = $cell;

            $depthToRowIndexMap[$depth] += $cell->rowspan;
        };

        /** @psalm-suppress MixedAssignment */
        foreach ($treeIterator as $key => $node) {
            if ($key === self::MATRIX_KEY) {
                break;
            }

            $depth = $treeIterator->getDepth();

            if (!is_array($node)) {
                continue;
            }

            if (!isset($variableNodeByDepth[$depth])) {
                $variableNodeByDepth[$depth] = $currentVariableNode;
            } else {
                $currentVariableNode = $variableNodeByDepth[$depth];
            }

            if (!isset($variableDepthByDepth[$depth])) {
                $variableDepthByDepth[$depth] = $currentVariableDepth;
            } else {
                $currentVariableDepth = $variableDepthByDepth[$depth];
            }

            if (isset($node['variableName'])) {
                // variable node detected. Save this to memory so as we don't lose our place as we move up and down the
                // tree
                $currentVariableNode = $node;
                $currentVariableDepth = $depth;
                $variableDepthByDepth[$depth] = $currentVariableDepth;
                $variableNodeByDepth[$depth] = $currentVariableNode;
                $variableFlushed = false;
                continue;
            }

            if (isset($node['categoryName'])) {
                $variableName = CrosstabExtractionUtilities::extractString('variableName', $currentVariableNode);

                $isFirst = $variableName === $firstVariable->name;
                $isLast = $variableName === $lastVariable->name;

                if (!$variableFlushed && $isLast) {
                    // write the data cells ("Frequencies," etc.) to the Y axis
                    foreach ($dataHeaders as $dataType => $dataHeader) {
                        $attr = ['class' => CrosstabCell::APPEARANCE_DATA_TYPE];

                        if ($dataType === $lastDataType) {
                            $attr['class'] .= ' ' . CrosstabCell::APPEARANCE_BOTTOM_CELL;
                        }

                        $yAxisLabel = CrosstabCell::header($dataHeader, attributes: $attr);
                        $write($yAxisLabel, $depth);
                    }

                    $variableFlushed = true;
                }

                if ($isLast) {
                    continue;
                }

                $categoryDescendantCount = CrosstabExtractionUtilities::extractAbsoluteInt(
                    'descendantCount',
                    $node
                ) ?: 1;

                if (!$variableFlushed && !$isFirst) {
                    // write the variable name to Y axis (for layered crosstabs)
                    $categorySiblingCount = CrosstabExtractionUtilities::extractAbsoluteInt(
                        'siblingCount',
                        $node
                    ) ?: 1;

                    $variableDescription = CrosstabExtractionUtilities::extractString(
                        'variableDescription',
                        $currentVariableNode
                    );

                    $yAxisLabel = CrosstabCell::header(
                        $variableDescription,
                        rowspan: max($categorySiblingCount * $categoryDescendantCount * $dataHeaderCount, 1),
                        attributes: ['class' => CrosstabCell::APPEARANCE_Y_AXIS_VARIABLE_LABEL]
                    );

                    $write($yAxisLabel, $currentVariableDepth);

                    $variableFlushed = true;
                }

                // write the current row variable category to the Y axis
                $categoryDescription = CrosstabExtractionUtilities::extractString(
                    'categoryDescription',
                    $node
                );

                $attr = ['class' => CrosstabCell::APPEARANCE_Y_AXIS_CATEGORY_LABEL];

                if ($node['isTotal'] ?? false) {
                    $attr['class'] .= ' ' . CrosstabCell::APPEARANCE_TOTAL_LABEL;
                }

                $yAxisCategoryLabel = CrosstabCell::header(
                    $categoryDescription,
                    rowspan: $categoryDescendantCount * $dataHeaderCount,
                    attributes: $attr
                );
                $write($yAxisCategoryLabel, $depth);
                continue;
            }

            if (!array_key_exists(CrosstabDataItem::FREQUENCY, $node)) {
                continue;
            }

            // write the data cells
            foreach ($dataHeaderKeys as $dataType) {
                $percent = self::isDataTypePercent($dataType);

                $value = CrosstabExtractionUtilities::extractNumeric($dataType, $node);

                $attr = ['class' => CrosstabCell::APPEARANCE_CELL];

                if ($node[CrosstabDataItem::IS_TOTAL] ?? false) {
                    $attr['class'] .= ' ' . CrosstabCell::APPEARANCE_TOTAL;
                }

                if ($dataType === $lastDataType) {
                    $attr['class'] .= ' ' . CrosstabCell::APPEARANCE_BOTTOM_CELL;
                }

                $dataCell = CrosstabCell::dataCell($this->formatNumber($value, $percent), $value, attributes: $attr);

                $write($dataCell, $depth + ($colCounter % $colCategoryCount));
            }

            $colCounter++;
        }

        /** @var array<int, array<int, CrosstabCell>> $rows */
        return new Crosstab($rows, self::getMatrix($treeIterator));
    }

    /**
     * @param CrosstabVariable $rowVariable
     * @param CrosstabVariable $colVariable
     * @param positive-int $xAxisWidth
     * @param positive-int $yAxisWidth
     * @return array<int, array<int, CrosstabCell>>
     */
    private function buildCrosstabHeader(
        CrosstabVariable $rowVariable,
        CrosstabVariable $colVariable,
        int $xAxisWidth,
        int $yAxisWidth
    ): array {
        $tableWidth = $xAxisWidth + $yAxisWidth;

        /** @var array<int, array<int, CrosstabCell>> $rows */
        $rows = [];

        if (null !== $this->title) {
            $rows[] = [CrosstabCell::header($this->title, $tableWidth, attributes: [
                'class' => CrosstabCell::APPEARANCE_TITLE])
            ];
        }

        $rows[] = [
            CrosstabCell::header($colVariable->description, $yAxisWidth, 2, attributes: [
                'class' => CrosstabCell::APPEARANCE_Y_AXIS
            ]),
            CrosstabCell::header($rowVariable->description, $xAxisWidth, attributes: [
                'class' => CrosstabCell::APPEARANCE_X_AXIS
            ])
        ];

        $xAxisRow = [];

        foreach ($colVariable->categories as $category) {
            $xAxisRow[] = CrosstabCell::header($category->description, attributes: [
                'class' => CrosstabCell::APPEARANCE_X_AXIS_CATEGORY_LABEL
            ]);
        }

        $xAxisRow[] = CrosstabCell::header($this->messageTotal, attributes: [
            'class' => CrosstabCell::APPEARANCE_X_AXIS_CATEGORY_LABEL
                . ' '
                . CrosstabCell::APPEARANCE_TOTAL_LABEL
        ]);

        return [...$rows, $xAxisRow];
    }

    /**
     * Gets the Y axis width for crosstabs and layered crosstabs
     * @param RecursiveIteratorIterator<RecursiveArrayIterator<array-key, mixed>> $treeIterator
     * @return positive-int
     */
    private static function getYAxisWidth(RecursiveIteratorIterator $treeIterator): int
    {
        /** @var RecursiveArrayIterator<array-key, mixed> $innerIterator */
        $innerIterator = $treeIterator->getInnerIterator();
        /** @var array<int, array<string, mixed>> $arr */
        $arr = $innerIterator->getArrayCopy();
        $descendantCount = (int)CrosstabExtractionUtilities::extractAbsoluteInt('descendantCount', $arr[0]);
        return ($descendantCount * 2) + 2;
    }
}
