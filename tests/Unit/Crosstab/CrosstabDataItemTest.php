<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Crosstab;

use CliffordVickrey\Crosstabs\Crosstab\CrosstabDataItem;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function json_encode;
use function serialize;
use function unserialize;

class CrosstabDataItemTest extends TestCase
{
    /**
     * @var CrosstabDataItem
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabDataItem $item;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->item = CrosstabDataItem::__set_state([
            'expectedFrequency' => 199.232,
            'expectedPercent' => 0.283,
            'frequency' => 256,
            'isTotal' => false,
            'params' => ['Platform' => 'Linux', 'Browser' => 'Chrome'],
            'percent' => 0.36363636363636,
            'weightedExpectedFrequency' => 155.32136684934378,
            'weightedExpectedPercent' => 0.22511406604042,
            'weightedFrequency' => 192.4608,
            'weightedPercent' => 0.27894187464509,
        ]);
    }

    /**
     * @return void
     */
    public function testConstruct(): void
    {
        $item = new CrosstabDataItem(
            expectedFrequency: $this->item->expectedFrequency,
            expectedPercent: $this->item->expectedPercent,
            frequency: $this->item->frequency,
            isTotal: $this->item->isTotal,
            params: $this->item->params,
            percent: $this->item->percent,
            weightedExpectedFrequency: $this->item->weightedExpectedFrequency,
            weightedExpectedPercent: $this->item->weightedExpectedPercent,
            weightedFrequency: $this->item->weightedFrequency,
            weightedPercent: $this->item->weightedPercent
        );

        self::assertEquals($this->item, $item);
    }

    /**
     * @return void
     */
    public function testSerialize(): void
    {
        $serialized = serialize($this->item);
        $unSerialized = unserialize($serialized);
        self::assertInstanceOf(CrosstabDataItem::class, $unSerialized);
        self::assertEquals($serialized, serialize($unSerialized));
    }

    /**
     * @return void
     */
    public function testCreateForLeafNode(): void
    {
        $expected = CrosstabDataItem::__set_state([]);
        $item = CrosstabDataItem::createForLeafNode();
        self::assertEquals($expected, $item);
    }

    /**
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $json = json_encode($this->item);
        self::assertIsString($json);
        self::assertEquals($this->item->toArray(), json_decode($json, true));
    }
}
