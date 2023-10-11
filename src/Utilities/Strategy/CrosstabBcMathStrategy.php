<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Utilities\Strategy;

use CliffordVickrey\Crosstabs\Utilities\CrosstabCastingUtilities;
use CliffordVickrey\Crosstabs\Utilities\CrosstabMathInterface;

use function bcadd;
use function bccomp;
use function bcdiv;
use function bcmul;
use function bcpow;
use function bcsub;

/**
 * @internal
 */
class CrosstabBcMathStrategy implements CrosstabMathInterface
{
    /**
     * @inheritDoc
     */
    public function add(mixed $addendA, mixed $addendB, int $scale = self::DEFAULT_SCALE): float
    {
        $addendA = (string)(CrosstabCastingUtilities::toNumeric($addendA) ?? 0.0);
        $addendB = (string)(CrosstabCastingUtilities::toNumeric($addendB) ?? 0.0);
        return (float)bcadd($addendA, $addendB, $scale);
    }

    /**
     * @inheritDoc
     */
    public function divide(mixed $dividend, mixed $divisor, int $scale = self::DEFAULT_SCALE): ?float
    {
        $divisor = (string)(CrosstabCastingUtilities::toNumeric($divisor) ?? 0.0);

        if (0 === bccomp('0', $divisor, $scale)) {
            return null;
        }

        $dividend = (string)(CrosstabCastingUtilities::toNumeric($dividend) ?? 0.0);
        return (float)bcdiv($dividend, $divisor, $scale);
    }

    /**
     * @inheritDoc
     */
    public function multiply(mixed $factorA, mixed $factorB, int $scale = self::DEFAULT_SCALE): float
    {
        $factorA = (string)(CrosstabCastingUtilities::toNumeric($factorA) ?? 0.0);
        $factorB = (string)(CrosstabCastingUtilities::toNumeric($factorB) ?? 0.0);
        return (float)bcmul($factorA, $factorB, $scale);
    }

    /**
     * @inheritDoc
     */
    public function subtract(mixed $minuend, mixed $subtrahend, int $scale = self::DEFAULT_SCALE): float
    {
        $minuend = (string)(CrosstabCastingUtilities::toNumeric($minuend) ?? 0.0);
        $subtrahend = (string)(CrosstabCastingUtilities::toNumeric($subtrahend) ?? 0.0);
        return (float)bcsub($minuend, $subtrahend, $scale);
    }

    /**
     * @inheritDoc
     */
    public function pow(mixed $num, mixed $exponent, int $scale = self::DEFAULT_SCALE): float
    {
        $num = (string)(CrosstabCastingUtilities::toNumeric($num) ?? 0.0);
        $exponent = (string)(CrosstabCastingUtilities::toNumeric($exponent) ?? 0.0);
        return (float)bcpow($num, $exponent, $scale);
    }
}
