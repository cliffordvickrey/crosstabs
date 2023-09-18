<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\SourceData;

use ArrayIterator;
use CliffordVickrey\Crosstabs\Exception\CrosstabUnexpectedValueException;
use CliffordVickrey\Crosstabs\SourceData\CrosstabSourceDataCollection;
use CliffordVickrey\Crosstabs\SourceData\CrosstabSourceDataRow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(CrosstabSourceDataCollection::class)]
class CrosstabSourceDataCollectionTest extends TestCase
{
    /**
     * @return void
     */
    public function testFromRawData(): void
    {
        $rawData = [
            [
                'foo' => 'bar',
                'n' => 5,
                'weight' => 5.5
            ],
            [
                'foo' => 'baz',
                'n' => 10,
                'weight' => 9
            ]
        ];

        $data = CrosstabSourceDataCollection::fromRawData($rawData, 'n', 'weight');

        self::assertCount(2, $data);

        $it = $data->getIterator();

        $row = $it->current();
        self::assertInstanceOf(CrosstabSourceDataRow::class, $row);
        self::assertEquals('bar', $row->getValue('foo'));
        self::assertEquals(5, $row->n);
        self::assertEquals(5.5, $row->weightedN);

        $it->next();
        $row = $it->current();
        self::assertInstanceOf(CrosstabSourceDataRow::class, $row);
        self::assertEquals('baz', $row->getValue('foo'));
        self::assertEquals(10, $row->n);
        self::assertEquals(9, $row->weightedN);

        $obj = new stdClass();
        $obj->foo = 'biz';
        $obj->n = 15;
        $obj->weightedN = 20;

        $data = CrosstabSourceDataCollection::fromRawData([
            $row,
            $obj,
            new ArrayIterator(['foo' => 'bar', 'n' => 5, 'weightedN' => 5.5])
        ], 'n', 'weightedN');
        $it = $data->getIterator();

        $row = $it->current();
        self::assertInstanceOf(CrosstabSourceDataRow::class, $row);
        self::assertEquals('baz', $row->getValue('foo'));
        self::assertEquals(10, $row->n);
        self::assertEquals(9, $row->weightedN);

        $it->next();
        $row = $it->current();
        self::assertInstanceOf(CrosstabSourceDataRow::class, $row);
        self::assertEquals('biz', $row->getValue('foo'));
        self::assertEquals(15, $row->n);
        self::assertEquals(20, $row->weightedN);

        $it->next();
        $row = $it->current();
        self::assertInstanceOf(CrosstabSourceDataRow::class, $row);
        self::assertEquals('bar', $row->getValue('foo'));
        self::assertEquals(5, $row->n);
        self::assertEquals(5.5, $row->weightedN);

        $this->expectException(CrosstabUnexpectedValueException::class);
        $this->expectExceptionMessage('Expected object, array, or Traversable; got bool');
        CrosstabSourceDataCollection::fromRawData([false]);
    }
}
