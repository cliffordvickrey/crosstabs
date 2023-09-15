<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\NumberFormatting;

use NumberFormatter;

use function sprintf;

/**
 * @internal A simple utility to retrieve and store number formatters
 */
class CrosstabNumberFormatterFlyweight implements CrosstabNumberFormatterFlyweightInterface
{
    /** @var array<string, NumberFormatter> */
    private array $formatters = [];

    /**
     * @inheritDoc
     */
    public function getNumberFormatter(
        CrosstabNumberFormatterType $type,
        string $locale,
        int $scale = 0
    ): NumberFormatter {
        $key = sprintf('%s|%s|%d', $type->name, $locale, $scale);

        if (isset($this->formatters[$key])) {
            return $this->formatters[$key];
        }

        $nf = new NumberFormatter($locale, $type->toIntlConstant());
        $nf->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $scale);
        $this->formatters[$key] = $nf;
        return $nf;
    }
}
