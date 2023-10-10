<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\NumberFormatting;

use CliffordVickrey\Crosstabs\NumberFormatting\CrosstabPolyfillNumberFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrosstabPolyfillNumberFormatter::class)]
class CrosstabPolyfillNumberFormatterTest extends TestCase
{
    /**
     * @return void
     */
    public function testFormat(): void
    {
        $formatter = new CrosstabPolyfillNumberFormatter();
        $formatter->setAttribute(6, 2);
        self::assertEquals('34,534.40', $formatter->format(34534.4));
        $formatter = new CrosstabPolyfillNumberFormatter(true);
        $formatter->setAttribute(6, 4);
        self::assertEquals('%54.4533', $formatter->format(.5445334));
    }

    /**
     * @return void
     */
    public function testGetAttribute(): void
    {
        $formatter = new CrosstabPolyfillNumberFormatter();
        $formatter->setAttribute(6, 2);
        self::assertEquals(2, $formatter->getAttribute(6));
        self::assertFalse($formatter->getAttribute(1));
    }

    /**
     * @return void
     */
    public function testGetLocale(): void
    {
        $formatter = new CrosstabPolyfillNumberFormatter();
        self::assertEquals('en_US', $formatter->getLocale());
    }

    /**
     * @return void
     */
    public function testSetAttribute(): void
    {
        $formatter = new CrosstabPolyfillNumberFormatter();
        self::assertTrue($formatter->setAttribute(6, 2));
        self::assertFalse($formatter->setAttribute(1, 1));
    }
}
