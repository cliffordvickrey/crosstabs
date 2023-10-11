<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Utilities;

use CliffordVickrey\Crosstabs\Utilities\CrosstabMath;
use CliffordVickrey\Crosstabs\Utilities\Strategy\CrosstabBcMathStrategy;
use CliffordVickrey\Crosstabs\Utilities\Strategy\CrosstabFloatingPointMathStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function extension_loaded;
use function round;

#[CoversClass(CrosstabBcMathStrategy::class)]
#[CoversClass(CrosstabMath::class)]
#[CoversClass(CrosstabFloatingPointMathStrategy::class)]
class CrosstabMathTest extends TestCase
{
    /**
     * @var CrosstabMath
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabMath $mathWithBcMath;

    /**
     * @var CrosstabMath
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabMath $mathWithoutBcMath;

    /**
     * @return void
     */
    public function setUp(): void
    {
        self::assertTrue(extension_loaded('bcmath'));

        $this->mathWithBcMath = new CrosstabMath();
        $this->mathWithoutBcMath = new CrosstabMath(false);
    }

    /**
     * @return void
     */
    public function testAdd(): void
    {
        $a = 1;
        $b = 2;

        self::assertEquals(3.0, $this->mathWithBcMath->add($a, $b));
        self::assertEquals(3.0, $this->mathWithoutBcMath->add($a, $b));
    }

    /**
     * @return void
     */
    public function testSubtract(): void
    {
        $a = 1;
        $b = 2;

        self::assertEquals(-1.0, $this->mathWithBcMath->subtract($a, $b));
        self::assertEquals(-1.0, $this->mathWithoutBcMath->subtract($a, $b));
    }


    /**
     * @return void
     */
    public function testDivide(): void
    {
        $a = 1;
        $b = 2;

        self::assertEquals(0.5, $this->mathWithBcMath->divide($a, $b));
        self::assertEquals(0.5, $this->mathWithoutBcMath->divide($a, $b));

        $b = null;

        self::assertNull($this->mathWithBcMath->divide($a, $b));
        self::assertNull($this->mathWithoutBcMath->divide($a, $b));
    }

    /**
     * @return void
     */
    public function testMultiply(): void
    {
        $a = .1;
        $b = .9;

        self::assertEquals(0.09, $this->mathWithBcMath->multiply($a, $b));
        self::assertNotEquals(0.09, $this->mathWithoutBcMath->multiply($a, $b, 17));
        self::assertEquals(0.09, round($this->mathWithoutBcMath->multiply($a, $b, 17), 2));
    }

    /**
     * @return void
     */
    public function testPow(): void
    {
        $a = 2;
        $b = 2;

        self::assertEquals(4.0, $this->mathWithBcMath->pow($a, $b));
        self::assertEquals(4.0, $this->mathWithoutBcMath->pow($a, $b));
    }
}
