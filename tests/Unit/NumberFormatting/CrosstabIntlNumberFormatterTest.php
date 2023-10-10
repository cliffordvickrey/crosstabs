<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\NumberFormatting;

use CliffordVickrey\Crosstabs\NumberFormatting\CrosstabIntlNumberFormatter;
use NumberFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrosstabIntlNumberFormatter::class)]
class CrosstabIntlNumberFormatterTest extends TestCase
{
    /**
     * @return void
     */
    public function testFormat(): void
    {
        $nf = new CrosstabIntlNumberFormatter(new NumberFormatter('en_US', NumberFormatter::DECIMAL));
        self::assertEquals('35', $nf->format(35));
        self::assertEquals('35', $nf->format(35, NumberFormatter::TYPE_INT32));
    }

    /**
     * @return void
     */
    public function testSetAttribute(): void
    {
        $nf = new CrosstabIntlNumberFormatter(new NumberFormatter('en_US', NumberFormatter::DECIMAL));
        self::assertTrue($nf->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2));
        self::assertEquals(2, $nf->getAttribute(NumberFormatter::MAX_FRACTION_DIGITS));
    }

    /**
     * @return void
     */
    public function testGetLocale(): void
    {
        $nf = new CrosstabIntlNumberFormatter(new NumberFormatter('de_DE', NumberFormatter::DECIMAL));
        self::assertEquals('de_DE', $nf->getLocale());
    }
}
