<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Utilities;

use CliffordVickrey\Crosstabs\Exception\CrosstabUnexpectedValueException;
use CliffordVickrey\Crosstabs\Utilities\CrosstabExtractionUtilities;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(CrosstabExtractionUtilities::class)]
class CrosstabExtractionUtilitiesTest extends TestCase
{
    /**
     * @return void
     */
    public function testExtractFloat(): void
    {
        self::assertEquals(10.5, CrosstabExtractionUtilities::extractFloat('key', ['key' => 10.5]));
        self::assertEquals(null, CrosstabExtractionUtilities::extractFloat('key', ['key' => new stdClass()]));
    }

    /**
     * @return void
     */
    public function testExtractNumeric(): void
    {
        self::assertEquals(10.5, CrosstabExtractionUtilities::extractNumeric('key', ['key' => '10.5']));
    }

    /**
     * @return void
     */
    public function testExtractAbsoluteInt(): void
    {
        self::assertEquals(10, CrosstabExtractionUtilities::extractAbsoluteInt('key', ['key' => -10]));
    }

    /**
     * @return void
     */
    public function testExtractPositiveInt(): void
    {
        self::assertEquals(10, CrosstabExtractionUtilities::extractPositiveInt('key', ['key' => 10]));
        self::assertEquals(null, CrosstabExtractionUtilities::extractPositiveInt('key', ['key' => -10]));
    }

    /**
     * @return void
     */
    public function testExtractString(): void
    {
        self::assertEquals('blah', CrosstabExtractionUtilities::extractString('key', ['key' => 'blah']));
    }

    /**
     * @return void
     */
    public function testExtractNonEmptyString(): void
    {
        self::assertEquals('blah', CrosstabExtractionUtilities::extractNonEmptyString('key', ['key' => 'blah']));
        self::assertEquals(null, CrosstabExtractionUtilities::extractNonEmptyString('key', ['key' => '']));
    }

    /**
     * @return void
     */
    public function testExtractNonEmptyStringRequired(): void
    {
        self::assertEquals('blah', CrosstabExtractionUtilities::extractNonEmptyStringRequired('key', [
            'key' => 'blah'
        ]));
        $this->expectException(CrosstabUnexpectedValueException::class);
        $this->expectExceptionMessage('Expected non-empty-string; got string');
        self::assertEquals(null, CrosstabExtractionUtilities::extractNonEmptyStringRequired('key', ['key' => '']));
    }

    /**
     * @return void
     */
    public function testExtractPositiveIntRequired(): void
    {
        self::assertEquals(10, CrosstabExtractionUtilities::extractPositiveIntRequired('key', ['key' => 10]));
        $this->expectException(CrosstabUnexpectedValueException::class);
        $this->expectExceptionMessage('Expected positive-int; got string');
        self::assertEquals(null, CrosstabExtractionUtilities::extractPositiveIntRequired('key', ['key' => 0]));
    }
}
