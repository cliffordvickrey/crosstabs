<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Helper;

use CliffordVickrey\Crosstabs\Options\CrosstabPercentType;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use CliffordVickrey\Crosstabs\Utilities\CrosstabMathUtilities;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * @inheritDoc
 */
interface CrosstabTreeBuilderInterface
{
    /**
     * Recursively iterates through the variable tree and populates it with data
     * @param CrosstabVariableCollection $variables
     * @param array{n: array<string, float>, weightedN: array<string, float>} $totals
     * @param CrosstabPercentType $percentType
     * @param non-empty-string $messageTotal
     * @param positive-int $scale
     * @return RecursiveIteratorIterator<RecursiveArrayIterator<array-key, mixed>>
     */
    public function buildTree(
        CrosstabVariableCollection $variables,
        array $totals,
        CrosstabPercentType $percentType = CrosstabPercentType::TOTAL,
        string $messageTotal = 'Total',
        int $scale = CrosstabMathUtilities::DEFAULT_SCALE
    ): RecursiveIteratorIterator;
}
