<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Utilities;

use CliffordVickrey\Crosstabs\Utilities\CrosstabCastingUtilities;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

#[CoversClass(CrosstabCastingUtilities::class)]
class CrosstabCastingUtilitiesTest extends TestCase
{
    /**
     * @return void
     */
    public function testToAbsoluteInt(): void
    {
        self::assertEquals(100, CrosstabCastingUtilities::toAbsoluteInt(-100));
        self::assertEquals(null, CrosstabCastingUtilities::toAbsoluteInt(new stdClass()));
    }

    /**
     * @return void
     */
    public function testToInt(): void
    {
        self::assertEquals(100, CrosstabCastingUtilities::toAbsoluteInt(100));
        self::assertEquals(null, CrosstabCastingUtilities::toAbsoluteInt(new stdClass()));
    }

    /**
     * @return void
     */
    public function testToNumeric(): void
    {
        self::assertEquals(100.05, CrosstabCastingUtilities::toNumeric(100.05));
        self::assertEquals(100, CrosstabCastingUtilities::toNumeric(100.00));
        self::assertEquals(100, CrosstabCastingUtilities::toNumeric('100.00'));
        self::assertEquals(null, CrosstabCastingUtilities::toNumeric(new stdClass()));
    }

    /**
     * @return void
     */
    public function testToNonEmptyString(): void
    {
        self::assertEquals(
            "I'd buy that for a dollar!",
            CrosstabCastingUtilities::toNonEmptyString("I'd buy that for a dollar!")
        );
        self::assertEquals(null, CrosstabCastingUtilities::toNonEmptyString(''));
    }

    /**
     * @return void
     */
    public function testToString(): void
    {
        $anon = new class implements Stringable {
            /**
             * @inheritDoc
             */
            public function __toString(): string
            {
                return "I'd buy that for a dollar!";
            }
        };

        self::assertEquals('1', CrosstabCastingUtilities::toString(1));
        self::assertEquals("I'd buy that for a dollar!", CrosstabCastingUtilities::toString($anon));
    }

    /**
     * @return void
     */
    public function testToPositiveInt(): void
    {
        self::assertEquals(100, CrosstabCastingUtilities::toPositiveInt(100));
        self::assertEquals(null, CrosstabCastingUtilities::toPositiveInt(-100));
        self::assertEquals(null, CrosstabCastingUtilities::toPositiveInt(new stdClass()));
    }
}
