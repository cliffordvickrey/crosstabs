<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Utilities;

use function extension_loaded;
use function pow;

final readonly class CrosstabMath
{
    private bool $bcMathLoaded;

    /**
     *
     */
    public function __construct(?bool $useBcMath = null)
    {
        if (null !== $useBcMath) {
            $this->bcMathLoaded = $useBcMath;
        } else {
            $this->bcMathLoaded = extension_loaded('bcmath');
        }
    }

    /**
     * @param mixed $addendA
     * @param mixed $addendB
     * @param int $scale
     * @return float
     */
    public function add(mixed $addendA, mixed $addendB, int $scale = CrosstabMathUtilities::DEFAULT_SCALE): float
    {
        if ($this->bcMathLoaded) {
            return CrosstabMathUtilities::add($addendA, $addendB, $scale);
        }

        $a = (float)(CrosstabCastingUtilities::toNumeric($addendA) ?? 0.0);
        $b = (float)(CrosstabCastingUtilities::toNumeric($addendB) ?? 0.0);
        return $a + $b;
    }

    /**
     * @param mixed $minuend
     * @param mixed $subtrahend
     * @param int $scale
     * @return float
     */
    public function subtract(
        mixed $minuend,
        mixed $subtrahend,
        int $scale = CrosstabMathUtilities::DEFAULT_SCALE
    ): float {
        if ($this->bcMathLoaded) {
            return CrosstabMathUtilities::subtract($minuend, $subtrahend, $scale);
        }

        $a = (float)(CrosstabCastingUtilities::toNumeric($minuend) ?? 0.0);
        $b = (float)(CrosstabCastingUtilities::toNumeric($subtrahend) ?? 0.0);
        return $a - $b;
    }

    /**
     * @param mixed $dividend
     * @param mixed $divisor
     * @param int $scale
     * @return float|null
     */
    public function divide(
        mixed $dividend,
        mixed $divisor,
        int $scale = CrosstabMathUtilities::DEFAULT_SCALE
    ): ?float {
        if ($this->bcMathLoaded) {
            return CrosstabMathUtilities::divide($dividend, $divisor, $scale);
        }

        $b = (float)(CrosstabCastingUtilities::toNumeric($divisor) ?? 0.0);

        if (0.0 === $b) {
            return null;
        }

        $a = (float)(CrosstabCastingUtilities::toNumeric($dividend) ?? 0.0);

        return $a / $b;
    }

    /**
     * @param mixed $factorA
     * @param mixed $factorB
     * @param int $scale
     * @return float
     */
    public function multiply(
        mixed $factorA,
        mixed $factorB,
        int $scale = CrosstabMathUtilities::DEFAULT_SCALE
    ): float {
        if ($this->bcMathLoaded) {
            return CrosstabMathUtilities::multiply($factorA, $factorB, $scale);
        }

        $a = (float)(CrosstabCastingUtilities::toNumeric($factorA) ?? 0.0);
        $b = (float)(CrosstabCastingUtilities::toNumeric($factorB) ?? 0.0);

        return $a * $b;
    }

    /**
     * @param mixed $num
     * @param mixed $exponent
     * @param int $scale
     * @return float
     */
    public function pow(mixed $num, mixed $exponent, int $scale = CrosstabMathUtilities::DEFAULT_SCALE): float
    {
        if ($this->bcMathLoaded) {
            return CrosstabMathUtilities::pow($num, $exponent, $scale);
        }

        $a = (float)(CrosstabCastingUtilities::toNumeric($num) ?? 0.0);
        $b = (float)(CrosstabCastingUtilities::toNumeric($exponent) ?? 0.0);

        return pow($a, $b);
    }
}
