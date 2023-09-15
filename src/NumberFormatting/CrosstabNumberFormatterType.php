<?php

namespace CliffordVickrey\Crosstabs\NumberFormatting;

use NumberFormatter;

/**
 * @internal
 */
enum CrosstabNumberFormatterType
{
    case DECIMAL;
    case PERCENT;

    /**
     * @return int
     */
    public function toIntlConstant(): int
    {
        if (self::DECIMAL === $this) {
            return NumberFormatter::DECIMAL;
        }

        return NumberFormatter::PERCENT;
    }
}
