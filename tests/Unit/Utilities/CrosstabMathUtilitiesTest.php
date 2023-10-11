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
}
