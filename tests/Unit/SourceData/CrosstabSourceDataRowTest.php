<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\SourceData;

use CliffordVickrey\Crosstabs\SourceData\CrosstabSourceDataRow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrosstabSourceDataRow::class)]
class CrosstabSourceDataRowTest extends TestCase
{
    /**
     * @var CrosstabSourceDataRow
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabSourceDataRow $row;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->row = new CrosstabSourceDataRow(['catchphrase' => "I'd buy that for a dollar!"]);
    }

    /**
     * @return void
     */
    public function testWeightedN(): void
    {
        $row = new CrosstabSourceDataRow([], 5);
        self::assertEquals(5, $row->weightedN);
        $row = new CrosstabSourceDataRow([], weightedN: 10);
        self::assertEquals(10, $row->weightedN);
    }

    /**
     * @return void
     */
    public function testGetValue(): void
    {
        self::assertEquals("I'd buy that for a dollar!", $this->row->getValue('catchphrase'));
    }

    /**
     * @return void
     */
    public function testCount(): void
    {
        self::assertCount(1, $this->row);
    }

    /**
     * @return void
     */
    public function testGetIterator(): void
    {
        self::assertEquals("I'd buy that for a dollar!", $this->row->getIterator()->offsetGet('catchphrase'));
    }
}
