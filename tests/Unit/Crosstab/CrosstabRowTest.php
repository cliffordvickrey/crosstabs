<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Crosstab;

use CliffordVickrey\Crosstabs\Crosstab\CrosstabCell;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabRow;
use CliffordVickrey\Crosstabs\Exception\CrosstabOutOfBoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function json_encode;
use function serialize;
use function unserialize;

#[CoversClass(CrosstabRow::class)]
class CrosstabRowTest extends TestCase
{
    /**
     * @var CrosstabRow
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabRow $row;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $cells = [
            CrosstabCell::__set_state([
                'attributes' => ['class' => '__crosstab-cell'],
                'colspan' => 1,
                'isHeader' => false,
                'rawValue' => 256,
                'rowspan' => 1,
                'textContent' => '256'
            ])
        ];

        $this->row = CrosstabRow::__set_state($cells);
    }

    /**
     * @return void
     */
    public function testSetState(): void
    {
        self::assertEquals(256, $this->row[0]->rawValue);

        // array style
        $cells = [
            [
                'attributes' => ['class' => '__crosstab-cell'],
                'colspan' => 1,
                'isHeader' => false,
                'rawValue' => 256,
                'rowspan' => 1,
                'textContent' => '256'
            ]
        ];

        $row = CrosstabRow::__set_state($cells);

        self::assertEquals(256, $row[0]->rawValue);
    }

    /**
     * @return void
     */
    public function testClone(): void
    {
        $cloned = clone $this->row;

        self::assertEquals($this->row, $cloned);

        $cloned[0]->textContent = 'whoops!';

        self::assertNotEquals($this->row[0]->textContent, $cloned[0]->textContent);
    }

    /**
     * @return void
     */
    public function testIsHeader(): void
    {
        self::assertFalse($this->row->isHeader());
        $this->row[0]->isHeader = true;
        self::assertTrue($this->row->isHeader());
    }

    /**
     * @return void
     */
    public function testGetWidth(): void
    {
        self::assertEquals(1, $this->row->getWidth());

        $this->row[] = CrosstabCell::header('something', 3);
        $this->row[2] = CrosstabCell::header('something else');

        self::assertEquals(5, $this->row->getWidth());
    }

    /**
     * @return void
     */
    public function testGetIterator(): void
    {
        $k = 0;

        foreach ($this->row as $cell) {
            self::assertInstanceOf(CrosstabCell::class, $cell);
            $k++;
        }

        self::assertCount($k, $this->row);
    }

    /**
     * @return void
     */
    public function testSerialize(): void
    {
        $serialized = serialize($this->row);
        $unSerialized = unserialize($serialized);
        self::assertInstanceOf(CrosstabRow::class, $unSerialized);
        self::assertEquals($this->row->toArray(), $unSerialized->toArray());
    }

    /**
     * @return void
     */
    public function testJsonEncode(): void
    {
        $json = json_encode($this->row);
        self::assertIsString($json);
        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        $obj = CrosstabRow::__set_state($decoded);
        self::assertEquals($this->row, $obj);
    }

    /**
     * @return void
     */
    public function testOffsetExists(): void
    {
        self::assertTrue(isset($this->row[0]));
        self::assertFalse(isset($this->row[1]));
        self::assertFalse(isset($this->row['blah']));
    }

    /**
     * @return void
     */
    public function testOffsetGet(): void
    {
        self::assertInstanceOf(CrosstabCell::class, $this->row[0]);
        $this->expectException(CrosstabOutOfBoundException::class);
        $this->expectExceptionMessage('Illegal offset, "1"');
        $this->row[1]; // @phpstan-ignore-line
    }

    /**
     * @return void
     */
    public function testOffsetUnset(): void
    {
        unset($this->row[0]);
        self::assertCount(0, $this->row);
    }
}
