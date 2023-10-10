<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\NumberFormatting;

use CliffordVickrey\Crosstabs\NumberFormatting\CrosstabNumberFormatterFlyweight;
use CliffordVickrey\Crosstabs\NumberFormatting\CrosstabNumberFormatterType;
use NumberFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function extension_loaded;
use function preg_replace;

#[CoversClass(CrosstabNumberFormatterFlyweight::class)]
class CrosstabNumberFormatterFlyweightTest extends TestCase
{
    /**
     * @return void
     */
    public function testGetNumberFormatter(): void
    {
        self::assertTrue(extension_loaded('intl'), 'PHP intl extension is missing');

        $flyweight = new CrosstabNumberFormatterFlyweight();

        // test percent format
        $nf = $flyweight->getNumberFormatter(CrosstabNumberFormatterType::PERCENT, 'en_US', 2);
        self::assertEquals(2, $nf->getAttribute(NumberFormatter::MAX_FRACTION_DIGITS));
        self::assertEquals('en_US', $nf->getLocale());
        self::assertEquals('12.05%', $nf->format(.1205));

        // test memoization
        $nf->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 3);
        $nf = $flyweight->getNumberFormatter(CrosstabNumberFormatterType::PERCENT, 'en_US', 2);
        self::assertEquals(3, $nf->getAttribute(NumberFormatter::MAX_FRACTION_DIGITS));
        self::assertEquals('en_US', $nf->getLocale());
        self::assertEquals('12.005%', $nf->format(.12005));

        // test scale
        $nf = $flyweight->getNumberFormatter(CrosstabNumberFormatterType::PERCENT, 'en_US', 4);
        self::assertEquals(4, $nf->getAttribute(NumberFormatter::MAX_FRACTION_DIGITS));
        self::assertEquals('en_US', $nf->getLocale());
        self::assertEquals('12.0005%', $nf->format(.120005));

        // test separate locale
        $nf = $flyweight->getNumberFormatter(CrosstabNumberFormatterType::PERCENT, 'es_ES', 2);
        self::assertEquals(2, $nf->getAttribute(NumberFormatter::MAX_FRACTION_DIGITS));
        self::assertEquals('es_ES', $nf->getLocale());
        self::assertEquals('12,05 %', preg_replace('/\s+/u', ' ', (string)$nf->format(.1205)));

        // test decimal format
        $nf = $flyweight->getNumberFormatter(CrosstabNumberFormatterType::DECIMAL, 'en_US', 2);
        self::assertEquals(2, $nf->getAttribute(NumberFormatter::MAX_FRACTION_DIGITS));
        self::assertEquals('en_US', $nf->getLocale());
        self::assertEquals('0.12', $nf->format(.1205));
    }
}
