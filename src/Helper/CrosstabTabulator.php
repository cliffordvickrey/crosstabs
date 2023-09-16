<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Helper;

use CliffordVickrey\Crosstabs\Exception\CrosstabLogicException;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use CliffordVickrey\Crosstabs\SourceData\CrosstabSourceDataCollection;
use CliffordVickrey\Crosstabs\Utilities\CrosstabCastingUtilities;
use CliffordVickrey\Crosstabs\Utilities\CrosstabMathUtilities;

use function array_flip;
use function array_intersect_key;
use function http_build_query;

class CrosstabTabulator implements CrosstabTabulatorInterface
{
    /**
     * @inheritDoc
     */
    public function tabulate(
        CrosstabVariableCollection $variables,
        CrosstabSourceDataCollection $sourceData,
        int $scale = CrosstabMathUtilities::DEFAULT_SCALE
    ): array {
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
                        $scale
                    );
                }

                if (!isset($totals['weightedN'][$key])) {
                    $totals['weightedN'][$key] = (float)$sourceRow->weightedN;
                } else {
                    $totals['weightedN'][$key] = CrosstabMathUtilities::add(
                        $totals['weightedN'][$key],
                        (float)$sourceRow->weightedN,
                        $scale
                    );
                }
            }
        }

        return $totals;
    }
}
