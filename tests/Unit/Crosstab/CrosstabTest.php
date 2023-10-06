<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Crosstab;

use CliffordVickrey\Crosstabs\Crosstab\Crosstab;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabInterface;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabRow;
use CliffordVickrey\Crosstabs\CrosstabBuilder;
use CliffordVickrey\Crosstabs\Options\CrosstabPercentType;
use CliffordVickrey\Crosstabs\Tests\Provider\TestDataProvider;
use CliffordVickrey\Crosstabs\Writer\CrosstabWriterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

use function call_user_func;
use function json_decode;
use function json_encode;
use function serialize;
use function unserialize;

#[CoversClass(Crosstab::class)]
class CrosstabTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstruct(): void
    {
        $provider = new TestDataProvider();
        $data = call_user_func($provider);

        $builder = new CrosstabBuilder();
        $builder->setColVariableName('Browser');
        $builder->setRowVariableName('Platform');
        $builder->setTitle('Browser Usage By Platform');
        $builder->setShowPercent(true);
        $builder->setPercentType(CrosstabPercentType::COLUMN_WITHIN_LAYER);
        $builder->setRawData($data);
        $crosstab = $builder->build();

        self::assertEquals($this->getSerializedCrosstab(), $crosstab);
    }

    /**
     * @return Crosstab
     */
    private function getSerializedCrosstab(): Crosstab
    {
        $provider = new TestDataProvider();
        $crosstab = $provider->getCrosstab();
        $crosstab->clearState();
        return $crosstab;
    }

    /**
     * @return void
     */
    public function testGetCell(): void
    {
        $crosstab = $this->getSerializedCrosstab();

        // add empty row
        $reflectionObj = new ReflectionObject($crosstab);
        $prop = $reflectionObj->getProperty('rows');
        /** @noinspection PhpExpressionResultUnusedInspection bugged inspection */
        $prop->setAccessible(true);
        /** @var list<CrosstabRow> $value */
        $value = $prop->getValue($crosstab);
        $value[] = new CrosstabRow();
        $prop->setValue($crosstab, $value);

        // un-memoized
        self::assertEquals('Browser Usage By Platform', $crosstab->getCell(0, 0)?->textContent);
        // memoized
        self::assertEquals('100%', $crosstab->getCell(8, 12)?->textContent);
        // null
        self::assertNull($crosstab->getCell(1, 0));
    }

    /**
     * @return void
     */
    public function testClone(): void
    {
        $crosstab = $this->getSerializedCrosstab();
        $cloned = clone $crosstab;

        self::assertEquals($crosstab, $cloned);

        $cloned->getCell(0, 0);

        /** @noinspection PhpConditionAlreadyCheckedInspection no it's *not* */
        self::assertNotEquals($crosstab, $cloned);

        $clonedAgain = clone $cloned;

        self::assertEquals($cloned, $clonedAgain);
    }

    /**
     * @return void
     */
    public function testWithoutData(): void
    {
        $crosstab = Crosstab::withoutData('No data!', 'Test Crosstab');
        self::assertEquals('Test Crosstab', $crosstab->getCell(0, 0)?->textContent);
        self::assertEquals('No data!', $crosstab->getCell(0, 1)?->textContent);
    }

    /**
     * @return void
     */
    public function testGetChiSquared(): void
    {
        self::assertEquals(598.1195632737818, $this->getSerializedCrosstab()->getChiSquared());
    }

    /**
     * @return void
     */
    public function testGetDegreesOfFreedom(): void
    {
        self::assertEquals(15, $this->getSerializedCrosstab()->getDegreesOfFreedom());
    }

    /**
     * @return void
     */
    public function testGetMatrix(): void
    {
        $matrix = $this->getSerializedCrosstab()->getMatrix();

        $expected = [
            [
                0,
                1,
                0,
                0,
                0,
                27
            ],
            [
                256,
                0,
                27,
                0,
                0,
                0
            ],
            [
                227,
                0,
                38,
                0,
                0,
                38
            ],
            [
                221,
                82,
                12,
                35,
                21,
                15
            ]
        ];

        foreach ($matrix as $y => $row) {
            self::assertLessThan(4, $y);
            foreach ($row as $x => $col) {
                self::assertLessThan(6, $x);
                /** @psalm-suppress MixedArrayAccess, InvalidArrayOffset */
                self::assertEquals($expected[$y][$x], $col->frequency);
            }
        }
    }

    /**
     * @return void
     */
    public function testGetIterator(): void
    {
        $crosstab = $this->getSerializedCrosstab();

        $k = 0;

        foreach ($crosstab as $row) {
            self::assertInstanceOf(CrosstabRow::class, $row);
            $k++;
        }

        self::assertCount($k, $crosstab);
    }

    /**
     * @return void
     */
    public function testToArray(): void
    {
        $arr = $this->getSerializedCrosstab()->toArray();
        self::assertArrayHasKey('rows', $arr);
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        self::assertIsArray($arr['rows']);
        self::assertArrayHasKey('matrix', $arr);
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        self::assertIsArray($arr['matrix']);
    }

    /**
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $crosstab = $this->getSerializedCrosstab();
        $json = (string)json_encode($crosstab);
        $arr = json_decode($json, true);
        self::assertIsArray($arr);
        self::assertArrayHasKey('rows', $arr);
        self::assertIsArray($arr['rows']);
        self::assertArrayHasKey('matrix', $arr);
        self::assertIsArray($arr['matrix']);
    }

    /**
     * @return void
     */
    public function testWrite(): void
    {
        $writer = new class implements CrosstabWriterInterface {
            /**
             * @inheritDoc
             */
            public function write(CrosstabInterface $crosstab, array $options = []): string
            {
                return 'markup';
            }

            /**
             * @inheritDoc
             */
            public function writeToFile(
                CrosstabInterface $crosstab,
                ?string $filename = null,
                array $options = []
            ): string {
                return 'crosstab.txt';
            }
        };

        $crosstab = $this->getSerializedCrosstab();
        $outputFile = __DIR__ . '/../../output/testWrite.html';

        $html = $crosstab->write();

        self::assertStringStartsWith('<table', $html);
        self::assertEquals($html, (string)$crosstab);
        self::assertStringEndsWith('testWrite.html', $crosstab->writeToFile($outputFile));
        self::assertEquals('markup', $crosstab->write(writer: $writer));
        self::assertEquals('crosstab.txt', $crosstab->writeToFile(writer: $writer));
    }

    /**
     * @return void
     */
    public function testSerialize(): void
    {
        $crosstab = $this->getSerializedCrosstab();
        $crosstab->getCell(0, 0);
        $serialized = serialize($crosstab);
        $unSerialized = unserialize($serialized);
        self::assertInstanceOf(Crosstab::class, $unSerialized);
        self::assertEquals($crosstab, $unSerialized);
    }
}
