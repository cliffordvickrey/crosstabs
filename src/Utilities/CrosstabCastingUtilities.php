<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Utilities;

use Stringable;

use function abs;
use function is_int;
use function is_numeric;
use function is_scalar;

/**
 * @internal
 */
class CrosstabCastingUtilities
{
    /**
     * @param mixed $val
     * @return int<0, max>|null
     */
    public static function toAbsoluteInt(mixed $val): ?int
    {
        $intVal = self::toInt($val);

        if (null === $intVal) {
            return null;
        }

        return abs($intVal);
    }

    /**
     * @param mixed $val
     * @return int|null
     */
    public static function toInt(mixed $val): ?int
    {
        $intVal = self::toNumeric($val);

        if (is_numeric($intVal)) {
            return (int)$intVal;
        }

        return null;
    }

    /**
     * @param mixed $val
     * @return float|int|null
     */
    public static function toNumeric(mixed $val): float|int|null
    {
        if (!is_scalar($val)) {
            $val = self::toString($val);
        }

        if (!is_numeric($val)) {
            return null;
        }

        if (is_int($val)) {
            return $val;
        }

        $val = (float)$val;

        if (CrosstabMathUtilities::isWholeNumber($val)) {
            return (int)$val;
        }

        return $val;
    }

    /**
     * @param mixed $val
     * @return string
     */
    public static function toString(mixed $val): string
    {
        if (null === $val || is_scalar($val) || ($val instanceof Stringable)) {
            return (string)$val;
        }

        return '';
    }

    /**
     * @param mixed $val
     * @return positive-int|null
     */
    public static function toPositiveInt(mixed $val): ?int
    {
        $intVal = self::toInt($val);

        if (null == $intVal) {
            return null;
        }

        return $intVal < 1 ? null : $intVal;
    }

    /**
     * @param mixed $val
     * @return non-empty-string|null
     */
    public static function toNonEmptyString(mixed $val): ?string
    {
        $str = self::toString($val);
        return '' === $str ? null : $str;
    }
}
