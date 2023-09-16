<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\NumberFormatting;

use CliffordVickrey\Crosstabs\NumberFormatting\CrosstabNumberFormatterType;
use NumberFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrosstabNumberFormatterType::class)]
class CrosstabNumberFormatterTypeTest extends TestCase
{
    /**
     * @return void
     */
    public function testToIntlConstant(): void
    {
        $type = CrosstabNumberFormatterType::DECIMAL;
        self::assertEquals(NumberFormatter::DECIMAL, $type->toIntlConstant());
        $type = CrosstabNumberFormatterType::PERCENT;
        self::assertEquals(NumberFormatter::PERCENT, $type->toIntlConstant());
    }
}
