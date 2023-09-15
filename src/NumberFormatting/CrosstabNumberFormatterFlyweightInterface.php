<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\NumberFormatting;

use NumberFormatter;

/**
 * @internal
 */
interface CrosstabNumberFormatterFlyweightInterface
{
    /**
     * @param CrosstabNumberFormatterType $type
     * @param non-empty-string $locale
     * @param int<0, max> $scale
     * @return NumberFormatter
     */
    public function getNumberFormatter(
        CrosstabNumberFormatterType $type,
        string $locale,
        int $scale = 0
    ): NumberFormatter;
}
