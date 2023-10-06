<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Crosstab;

use CliffordVickrey\Crosstabs\Crosstab\CrosstabCell;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function json_encode;
use function serialize;
use function unserialize;

#[CoversClass(CrosstabCell::class)]
class CrosstabCellTest extends TestCase
{
    /**
     * @var CrosstabCell
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabCell $cell;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->cell = CrosstabCell::__set_state([
            'attributes' => ['class' => '__crosstab-cell'],
            'colspan' => 1,
            'isHeader' => false,
            'rawValue' => 256,
            'rowspan' => 1,
            'textContent' => '256'
        ]);
    }

    /**
     * @return void
     */
    public function testSetState(): void
    {
        self::assertEquals(['class' => '__crosstab-cell'], $this->cell->attributes);
        self::assertEquals(1, $this->cell->colspan);
        self::assertFalse($this->cell->isHeader);
        self::assertEquals(256, $this->cell->rawValue);
        self::assertEquals(1, $this->cell->rowspan);
        self::assertEquals('256', $this->cell->textContent);
    }

    /**
     * @return void
     */
    public function testHeader(): void
    {
        $header = CrosstabCell::header('blah', 2, 4, ['class' => 'bold']);
        self::assertEquals(['class' => 'bold'], $header->attributes);
        self::assertEquals(2, $header->colspan);
        self::assertTrue($header->isHeader);
        self::assertNull($header->rawValue);
        self::assertEquals(4, $header->rowspan);
        self::assertEquals('blah', $header->textContent);
    }

    /**
     * @return void
     */
    public function testDataCell(): void
    {
        $header = CrosstabCell::dataCell('100%', 1, ['class' => 'bold']);
        self::assertEquals(['class' => 'bold'], $header->attributes);
        self::assertEquals(1, $header->colspan);
        self::assertFalse($header->isHeader);
        self::assertEquals(1, $header->rawValue);
        self::assertEquals(1, $header->rowspan);
        self::assertEquals('100%', $header->textContent);
    }

    /**
     * @return void
     */
    public function testToArray(): void
    {
        $expected = [
            'attributes' => ['class' => '__crosstab-cell'],
            'colspan' => 1,
            'isHeader' => false,
            'rawValue' => 256,
            'rowspan' => 1,
            'textContent' => '256'
        ];

        self::assertEquals($expected, $this->cell->toArray());
    }

    /**
     * @return void
     */
    public function testSerialize(): void
    {
        $serialized = serialize($this->cell);
        $unSerialized = unserialize($serialized);
        self::assertInstanceOf(CrosstabCell::class, $unSerialized);
        self::assertEquals($this->cell->toArray(), $unSerialized->toArray());
    }

    /**
     * @return void
     */
    public function testJsonEncode(): void
    {
        $json = json_encode($this->cell);
        self::assertIsString($json);
        self::assertEquals($this->cell->toArray(), json_decode($json, true));
    }
}
