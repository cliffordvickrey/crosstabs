<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Utilities;

use CliffordVickrey\Crosstabs\Utilities\CrosstabMathUtilities;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrosstabMathUtilities::class)]
class CrosstabMathUtilitiesTest extends TestCase
{
    /**
     * @return void
     */
    public function testIsWholeNumber(): void
    {
        self::assertTrue(CrosstabMathUtilities::isWholeNumber(10.0));
        self::assertFalse(CrosstabMathUtilities::isWholeNumber(10.5));
    }

    /**
     * @return void
     */
    public function testGetPowerSet(): void
    {
        $elements = ['A', 'B', 'C'];

        $powerSet = CrosstabMathUtilities::getPowerSet($elements);

        $expected = [
            [],
            ['A'],
            ['B'],
            ['B', 'A'],
            ['C'],
            ['C', 'A'],
            ['C', 'B'],
            ['C', 'B', 'A']
        ];

        self::assertEquals($expected, $powerSet);
    }

    /**
     * @return void
     */
    public function testAdd(): void
    {
        self::assertEquals(20.6912, CrosstabMathUtilities::add('10.1234', '10.5678'));
    }

    /**
     * @return void
     */
    public function testSubtract(): void
    {
        self::assertEquals(-0.4444, CrosstabMathUtilities::subtract('10.1234', '10.5678'));
    }

    /**
     * @return void
     */
    public function testDivide(): void
    {
        self::assertEquals(0.9579, CrosstabMathUtilities::divide('10.1234', '10.5678', 4));
        self::assertNull(CrosstabMathUtilities::divide('10.1234', '0'));
    }

    /**
     * @return void
     */
    public function testMultiply(): void
    {
        // this answer is wrong, but only because of a rounding quirk of bcmath
        self::assertEquals(106.98206, CrosstabMathUtilities::multiply('10.1234', '10.5678', 5));
    }

    /**
     * @return void
     */
    public function testPow(): void
    {
        self::assertEquals(1000, CrosstabMathUtilities::pow('10', '3'));
    }
}
