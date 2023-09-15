<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Utilities;

use function bcadd;
use function bccomp;
use function bcdiv;
use function bcmul;
use function bcpow;
use function bcsub;
use function floor;

/**
 * @internal
 */
class CrosstabMathUtilities
{
    public const DEFAULT_SCALE = 14;

    /**
     * @param int|float|numeric-string $val
     * @return bool
     */
    public static function isWholeNumber(int|float|string $val): bool
    {
        $val = (float)$val;

        if (floor($val) !== $val) {
            return false;
        }

        return true;
    }

    /**
     * @param list<string> $elements
     * @return list<list<string>>
     */
    public static function getPowerSet(array $elements): array
    {
        $results = [[]];

        foreach ($elements as $element) {
            foreach ($results as $combination) {
                $results[] = [$element, ...$combination];
            }
        }

        return $results;
    }

    /**
     * @param mixed $addendA
     * @param mixed $addendB
     * @param int $scale
     * @return float
     */
    public static function add(mixed $addendA, mixed $addendB, int $scale = self::DEFAULT_SCALE): float
    {
        $addendA = (string)(CrosstabCastingUtilities::toNumeric($addendA) ?? 0.0);
        $addendB = (string)(CrosstabCastingUtilities::toNumeric($addendB) ?? 0.0);
        return (float)bcadd($addendA, $addendB, $scale);
    }

    /**
     * @param mixed $minuend
     * @param mixed $subtrahend
     * @param int $scale
     * @return float
     */
    public static function subtract(mixed $minuend, mixed $subtrahend, int $scale = self::DEFAULT_SCALE): float
    {
        $minuend = (string)(CrosstabCastingUtilities::toNumeric($minuend) ?? 0.0);
        $subtrahend = (string)(CrosstabCastingUtilities::toNumeric($subtrahend) ?? 0.0);
        return (float)bcsub($minuend, $subtrahend, $scale);
    }


    /**
     * @param mixed $dividend
     * @param mixed $divisor
     * @param int $scale
     * @return float|null
     */
    public static function divide(mixed $dividend, mixed $divisor, int $scale = self::DEFAULT_SCALE): ?float
    {
        $dividend = (string)(CrosstabCastingUtilities::toNumeric($dividend) ?? 0.0);
        $divisor = (string)(CrosstabCastingUtilities::toNumeric($divisor) ?? 0.0);

        if (0 === bccomp('0', $divisor, $scale)) {
            return null;
        }

        return (float)bcdiv($dividend, $divisor, $scale);
    }

    /**
     * @param mixed $factorA
     * @param mixed $factorB
     * @param int $scale
     * @return float
     */
    public static function multiply(mixed $factorA, mixed $factorB, int $scale = self::DEFAULT_SCALE): float
    {
        $factorA = (string)(CrosstabCastingUtilities::toNumeric($factorA) ?? 0.0);
        $factorB = (string)(CrosstabCastingUtilities::toNumeric($factorB) ?? 0.0);
        return (float)bcmul($factorA, $factorB, $scale);
    }

    /**
     * @param mixed $num
     * @param mixed $exponent
     * @param int $scale
     * @return float
     */
    public static function pow(mixed $num, mixed $exponent, int $scale = self::DEFAULT_SCALE): float
    {
        $num = (string)(CrosstabCastingUtilities::toNumeric($num) ?? 0.0);
        $exponent = (string)(CrosstabCastingUtilities::toNumeric($exponent) ?? 0.0);
        return (float)bcpow($num, $exponent, $scale);
    }
}
