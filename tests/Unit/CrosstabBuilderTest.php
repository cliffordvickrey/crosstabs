<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit;

use CliffordVickrey\Crosstabs\Crosstab\Crosstab;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabCell;
use CliffordVickrey\Crosstabs\CrosstabBuilder;
use CliffordVickrey\Crosstabs\Exception\CrosstabInvalidArgumentException;
use CliffordVickrey\Crosstabs\Options\CrosstabPercentType;
use CliffordVickrey\Crosstabs\Tests\Provider\TestDataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function call_user_func;
use function sprintf;

#[CoversClass(CrosstabBuilder::class)]
class CrosstabBuilderTest extends TestCase
{
    /** @var list<array<string, mixed>> */
    private static array $data;
    /**
     * @var CrosstabBuilder
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabBuilder $builder;

    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        self::$data = call_user_func(new TestDataProvider());
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->builder = new CrosstabBuilder();
        $this->builder->setColVariableName('Browser');
        $this->builder->setRowVariableName('Platform');
        $this->builder->setTitle('Browser Usage By Platform');
        $this->builder->setShowPercent(true);
        $this->builder->setPercentType(CrosstabPercentType::COLUMN_WITHIN_LAYER);
        $this->builder->setRawData(self::$data);
    }

    /**
     * @return void
     */
    public function testBuild(): void
    {
        $crosstab = $this->builder->build();
        self::assertEquals(15, $crosstab->getDegreesOfFreedom());

        $matrix = $crosstab->getMatrix();
        self::assertCount(4, $matrix);
        self::assertCount(6, $matrix[0]);

        $expectedCells = [
            [0, 0, 'Browser Usage By Platform'],
            [0, 1, 'Platform'],
            [2, 1, 'Browser'],
            [2, 2, 'Chrome'],
            [3, 2, 'Edge'],
            [4, 2, 'Firefox'],
            [5, 2, 'IE'],
            [6, 2, 'Netscape'],
            [7, 2, 'Safari'],
            [8, 2, 'Total'],
            [0, 3, 'iOS'],
            [1, 3, 'Frequency'],
            [2, 3, '0'],
            [3, 3, '1'],
            [4, 3, '0'],
            [5, 3, '0'],
            [6, 3, '0'],
            [7, 3, '27'],
            [8, 3, '28'],
            [1, 4, '%'],
            [2, 4, '0%'],
            [3, 4, '1.2%'],
            [4, 4, '0%'],
            [5, 4, '0%'],
            [6, 4, '0%'],
            [7, 4, '33.75%'],
            [8, 4, '2.8%'],
            [0, 5, 'Linux'],
            [1, 5, 'Frequency'],
            [2, 5, '256'],
            [3, 5, '0'],
            [4, 5, '27'],
            [5, 5, '0'],
            [6, 5, '0'],
            [7, 5, '0'],
            [8, 5, '283'],
            [1, 6, '%'],
            [2, 6, '36.36%'],
            [3, 6, '0%'],
            [4, 6, '35.06%'],
            [5, 6, '0%'],
            [6, 6, '0%'],
            [7, 6, '0%'],
            [8, 6, '28.3%'],
            [0, 7, 'MacOSX'],
            [1, 7, 'Frequency'],
            [2, 7, '227'],
            [3, 7, '0'],
            [4, 7, '38'],
            [5, 7, '0'],
            [6, 7, '0'],
            [7, 7, '38'],
            [8, 7, '303'],
            [1, 8, '%'],
            [2, 8, '32.24%'],
            [3, 8, '0%'],
            [4, 8, '49.35%'],
            [5, 8, '0%'],
            [6, 8, '0%'],
            [7, 8, '47.5%'],
            [8, 8, '30.3%'],
            [0, 9, 'Windows'],
            [1, 9, 'Frequency'],
            [2, 9, '221'],
            [3, 9, '82'],
            [4, 9, '12'],
            [5, 9, '35'],
            [6, 9, '21'],
            [7, 9, '15'],
            [8, 9, '386'],
            [1, 10, '%'],
            [2, 10, '31.39%'],
            [3, 10, '98.8%'],
            [4, 10, '15.58%'],
            [5, 10, '100%'],
            [6, 10, '100%'],
            [7, 10, '18.75%'],
            [8, 10, '38.6%'],
            [0, 11, 'Total'],
            [1, 11, 'Frequency'],
            [2, 11, '704'],
            [3, 11, '83'],
            [4, 11, '77'],
            [5, 11, '35'],
            [6, 11, '21'],
            [7, 11, '80'],
            [8, 11, '1,000'],
            [1, 12, '%'],
            [2, 12, '100%'],
            [3, 12, '100%'],
            [4, 12, '100%'],
            [5, 12, '100%'],
            [6, 12, '100%'],
            [7, 12, '100%'],
            [8, 12, '100%']
        ];

        self::assertCrosstabHasExpectedCells($expectedCells, $crosstab);

        self::writeCrosstab($crosstab, __FUNCTION__);
    }

    /**
     * @param list<array{0: int, 1: int, 2: string}> $expectedCells
     * @param Crosstab $crosstab
     * @return void
     */
    private static function assertCrosstabHasExpectedCells(array $expectedCells, Crosstab $crosstab): void
    {
        foreach ($expectedCells as $expectedCell) {
            list ($x, $y, $expectedTextContent) = $expectedCell;
            $actualCell = $crosstab->getCell($x, $y);

            self::assertInstanceOf(
                CrosstabCell::class,
                $actualCell,
                sprintf('Expected cell to exist in crosstabs at X = %d, Y = %d', $x, $y)
            );

            self::assertEquals(
                $expectedTextContent,
                $actualCell->textContent,
                sprintf(
                    'Expected cell as X = %d, Y = %d to have text content "%s;" got "%s"',
                    $x,
                    $y,
                    $expectedTextContent,
                    $actualCell->textContent
                )
            );
        }
    }

    /**
     * @param Crosstab $crosstab
     * @param string $name
     * @return void
     */
    private static function writeCrosstab(Crosstab $crosstab, string $name): void
    {
        $outFile = $crosstab->writeToFile(__DIR__ . '/../output/' . $name . '.html');
        self::assertFileExists($outFile);
    }

    /**
     * @return void
     */
    public function testBuildWeighted(): void
    {
        $this->builder->setShowFrequency(false);
        $this->builder->setShowPercent(false);
        $this->builder->setShowWeightedFrequency(true);
        $this->builder->setShowWeightedExpectedPercent(true);
        $this->builder->setTitle('Weighted Browser Usage By Platform');

        $crosstab = $this->builder->build();
        self::assertEquals(15, $crosstab->getDegreesOfFreedom());

        $matrix = $crosstab->getMatrix();
        self::assertCount(4, $matrix);
        self::assertCount(6, $matrix[0]);

        $expectedCells = [
            [0, 0, 'Weighted Browser Usage By Platform'],
            [0, 1, 'Platform'],
            [2, 1, 'Browser'],
            [2, 2, 'Chrome'],
            [3, 2, 'Edge'],
            [4, 2, 'Firefox'],
            [5, 2, 'IE'],
            [6, 2, 'Netscape'],
            [7, 2, 'Safari'],
            [8, 2, 'Total'],
            [0, 3, 'iOS'],
            [1, 3, 'Frequency (Weighted)'],
            [2, 3, '0'],
            [3, 3, '0.96'],
            [4, 3, '0'],
            [5, 3, '0'],
            [6, 3, '0'],
            [7, 3, '32.7'],
            [8, 3, '33.67'],
            [1, 4, 'Expected % (Weighted)'],
            [2, 4, '3.43%'],
            [3, 4, '3.43%'],
            [4, 4, '3.43%'],
            [5, 4, '3.43%'],
            [6, 4, '3.43%'],
            [7, 4, '3.43%'],
            [8, 4, '3.43%'],
            [0, 5, 'Linux'],
            [1, 5, 'Frequency (Weighted)'],
            [2, 5, '192.46'],
            [3, 5, '0'],
            [4, 5, '28.42'],
            [5, 5, '0'],
            [6, 5, '0'],
            [7, 5, '0'],
            [8, 5, '220.88'],
            [1, 6, 'Expected % (Weighted)'],
            [2, 6, '22.51%'],
            [3, 6, '22.51%'],
            [4, 6, '22.51%'],
            [5, 6, '22.51%'],
            [6, 6, '22.51%'],
            [7, 6, '22.51%'],
            [8, 6, '22.51%'],
            [0, 7, 'MacOSX'],
            [1, 7, 'Frequency (Weighted)'],
            [2, 7, '233.79'],
            [3, 7, '0'],
            [4, 7, '42.83'],
            [5, 7, '0'],
            [6, 7, '0'],
            [7, 7, '35.94'],
            [8, 7, '312.56'],
            [1, 8, 'Expected % (Weighted)'],
            [2, 8, '31.85%'],
            [3, 8, '31.85%'],
            [4, 8, '31.85%'],
            [5, 8, '31.85%'],
            [6, 8, '31.85%'],
            [7, 8, '31.85%'],
            [8, 8, '31.85%'],
            [0, 9, 'Windows'],
            [1, 9, 'Frequency (Weighted)'],
            [2, 9, '263.72'],
            [3, 9, '67.67'],
            [4, 9, '14.31'],
            [5, 9, '36.37'],
            [6, 9, '18.52'],
            [7, 9, '13.51'],
            [8, 9, '414.1'],
            [1, 10, 'Expected % (Weighted)'],
            [2, 10, '42.2%'],
            [3, 10, '42.2%'],
            [4, 10, '42.2%'],
            [5, 10, '42.2%'],
            [6, 10, '42.2%'],
            [7, 10, '42.2%'],
            [8, 10, '42.2%'],
            [0, 11, 'Total'],
            [1, 11, 'Frequency (Weighted)'],
            [2, 11, '689.97'],
            [3, 11, '68.64'],
            [4, 11, '85.56'],
            [5, 11, '36.37'],
            [6, 11, '18.52'],
            [7, 11, '82.15'],
            [8, 11, '981.21'],
            [1, 12, 'Expected % (Weighted)'],
            [2, 12, '100%'],
            [3, 12, '100%'],
            [4, 12, '100%'],
            [5, 12, '100%'],
            [6, 12, '100%'],
            [7, 12, '100%'],
            [8, 12, '100%']
        ];

        self::assertCrosstabHasExpectedCells($expectedCells, $crosstab);

        self::writeCrosstab($crosstab, __FUNCTION__);
    }

    /**
     * @return void
     */
    public function testBuildLayered(): void
    {
        $this->builder->setTitle('Browser Usage By Platform and Device Type');
        $this->builder->addLayer('Device Type');
        $this->builder->setPercentType(CrosstabPercentType::COLUMN_WITHIN_LAYER);
        $crosstab = $this->builder->build();
        self::assertEquals(55, $crosstab->getDegreesOfFreedom());

        $matrix = $crosstab->getMatrix();
        self::assertCount(12, $matrix);
        self::assertCount(6, $matrix[0]);

        $expectedCells = [
            [0, 0, 'Browser Usage By Platform and Device Type'],
            [0, 1, 'Device Type'],
            [4, 1, 'Browser'],
            [4, 2, 'Chrome'],
            [5, 2, 'Edge'],
            [6, 2, 'Firefox'],
            [7, 2, 'IE'],
            [8, 2, 'Netscape'],
            [9, 2, 'Safari'],
            [10, 2, 'Total'],
            [0, 3, 'Desktop'],
            [1, 3, 'Platform'],
            [2, 3, 'iOS'],
            [3, 3, 'Frequency'],
            [4, 3, '0'],
            [5, 3, '0'],
            [6, 3, '0'],
            [7, 3, '0'],
            [8, 3, '0'],
            [9, 3, '0'],
            [10, 3, '0'],
            [3, 4, '%'],
            [4, 4, '0%'],
            [5, 4, '0%'],
            [6, 4, '0%'],
            [7, 4, '0%'],
            [8, 4, '0%'],
            [9, 4, '0%'],
            [10, 4, '0%'],
            [2, 5, 'Linux'],
            [3, 5, 'Frequency'],
            [4, 5, '256'],
            [5, 5, '0'],
            [6, 5, '27'],
            [7, 5, '0'],
            [8, 5, '0'],
            [9, 5, '0'],
            [10, 5, '283'],
            [3, 6, '%'],
            [4, 6, '36.36%'],
            [5, 6, '0%'],
            [6, 6, '35.06%'],
            [7, 6, '0%'],
            [8, 6, '0%'],
            [9, 6, '0%'],
            [10, 6, '29.12%'],
            [2, 7, 'MacOSX'],
            [3, 7, 'Frequency'],
            [4, 7, '227'],
            [5, 7, '0'],
            [6, 7, '38'],
            [7, 7, '0'],
            [8, 7, '0'],
            [9, 7, '38'],
            [10, 7, '303'],
            [3, 8, '%'],
            [4, 8, '32.24%'],
            [5, 8, '0%'],
            [6, 8, '49.35%'],
            [7, 8, '0%'],
            [8, 8, '0%'],
            [9, 8, '71.7%'],
            [10, 8, '31.17%'],
            [2, 9, 'Windows'],
            [3, 9, 'Frequency'],
            [4, 9, '221'],
            [5, 9, '82'],
            [6, 9, '12'],
            [7, 9, '35'],
            [8, 9, '21'],
            [9, 9, '15'],
            [10, 9, '386'],
            [3, 10, '%'],
            [4, 10, '31.39%'],
            [5, 10, '100%'],
            [6, 10, '15.58%'],
            [7, 10, '100%'],
            [8, 10, '100%'],
            [9, 10, '28.3%'],
            [10, 10, '39.71%'],
            [2, 11, 'Total'],
            [3, 11, 'Frequency'],
            [4, 11, '704'],
            [5, 11, '82'],
            [6, 11, '77'],
            [7, 11, '35'],
            [8, 11, '21'],
            [9, 11, '53'],
            [10, 11, '972'],
            [3, 12, '%'],
            [4, 12, '100%'],
            [5, 12, '100%'],
            [6, 12, '100%'],
            [7, 12, '100%'],
            [8, 12, '100%'],
            [9, 12, '100%'],
            [10, 12, '100%'],
            [0, 13, 'Mobile Device'],
            [1, 13, 'Platform'],
            [2, 13, 'iOS'],
            [3, 13, 'Frequency'],
            [4, 13, '0'],
            [5, 13, '1'],
            [6, 13, '0'],
            [7, 13, '0'],
            [8, 13, '0'],
            [9, 13, '21'],
            [10, 13, '22'],
            [3, 14, '%'],
            [4, 14, '-'],
            [5, 14, '100%'],
            [6, 14, '-'],
            [7, 14, '-'],
            [8, 14, '-'],
            [9, 14, '100%'],
            [10, 14, '100%'],
            [2, 15, 'Linux'],
            [3, 15, 'Frequency'],
            [4, 15, '0'],
            [5, 15, '0'],
            [6, 15, '0'],
            [7, 15, '0'],
            [8, 15, '0'],
            [9, 15, '0'],
            [10, 15, '0'],
            [3, 16, '%'],
            [4, 16, '-'],
            [5, 16, '0%'],
            [6, 16, '-'],
            [7, 16, '-'],
            [8, 16, '-'],
            [9, 16, '0%'],
            [10, 16, '0%'],
            [2, 17, 'MacOSX'],
            [3, 17, 'Frequency'],
            [4, 17, '0'],
            [5, 17, '0'],
            [6, 17, '0'],
            [7, 17, '0'],
            [8, 17, '0'],
            [9, 17, '0'],
            [10, 17, '0'],
            [3, 18, '%'],
            [4, 18, '-'],
            [5, 18, '0%'],
            [6, 18, '-'],
            [7, 18, '-'],
            [8, 18, '-'],
            [9, 18, '0%'],
            [10, 18, '0%'],
            [2, 19, 'Windows'],
            [3, 19, 'Frequency'],
            [4, 19, '0'],
            [5, 19, '0'],
            [6, 19, '0'],
            [7, 19, '0'],
            [8, 19, '0'],
            [9, 19, '0'],
            [10, 19, '0'],
            [3, 20, '%'],
            [4, 20, '-'],
            [5, 20, '0%'],
            [6, 20, '-'],
            [7, 20, '-'],
            [8, 20, '-'],
            [9, 20, '0%'],
            [10, 20, '0%'],
            [2, 21, 'Total'],
            [3, 21, 'Frequency'],
            [4, 21, '0'],
            [5, 21, '1'],
            [6, 21, '0'],
            [7, 21, '0'],
            [8, 21, '0'],
            [9, 21, '21'],
            [10, 21, '22'],
            [3, 22, '%'],
            [4, 22, '-'],
            [5, 22, '100%'],
            [6, 22, '-'],
            [7, 22, '-'],
            [8, 22, '-'],
            [9, 22, '100%'],
            [10, 22, '100%'],
            [0, 23, 'Tablet'],
            [1, 23, 'Platform'],
            [2, 23, 'iOS'],
            [3, 23, 'Frequency'],
            [4, 23, '0'],
            [5, 23, '0'],
            [6, 23, '0'],
            [7, 23, '0'],
            [8, 23, '0'],
            [9, 23, '6'],
            [10, 23, '6'],
            [3, 24, '%'],
            [4, 24, '-'],
            [5, 24, '-'],
            [6, 24, '-'],
            [7, 24, '-'],
            [8, 24, '-'],
            [9, 24, '100%'],
            [10, 24, '100%'],
            [2, 25, 'Linux'],
            [3, 25, 'Frequency'],
            [4, 25, '0'],
            [5, 25, '0'],
            [6, 25, '0'],
            [7, 25, '0'],
            [8, 25, '0'],
            [9, 25, '0'],
            [10, 25, '0'],
            [3, 26, '%'],
            [4, 26, '-'],
            [5, 26, '-'],
            [6, 26, '-'],
            [7, 26, '-'],
            [8, 26, '-'],
            [9, 26, '0%'],
            [10, 26, '0%'],
            [2, 27, 'MacOSX'],
            [3, 27, 'Frequency'],
            [4, 27, '0'],
            [5, 27, '0'],
            [6, 27, '0'],
            [7, 27, '0'],
            [8, 27, '0'],
            [9, 27, '0'],
            [10, 27, '0'],
            [3, 28, '%'],
            [4, 28, '-'],
            [5, 28, '-'],
            [6, 28, '-'],
            [7, 28, '-'],
            [8, 28, '-'],
            [9, 28, '0%'],
            [10, 28, '0%'],
            [2, 29, 'Windows'],
            [3, 29, 'Frequency'],
            [4, 29, '0'],
            [5, 29, '0'],
            [6, 29, '0'],
            [7, 29, '0'],
            [8, 29, '0'],
            [9, 29, '0'],
            [10, 29, '0'],
            [3, 30, '%'],
            [4, 30, '-'],
            [5, 30, '-'],
            [6, 30, '-'],
            [7, 30, '-'],
            [8, 30, '-'],
            [9, 30, '0%'],
            [10, 30, '0%'],
            [2, 31, 'Total'],
            [3, 31, 'Frequency'],
            [4, 31, '0'],
            [5, 31, '0'],
            [6, 31, '0'],
            [7, 31, '0'],
            [8, 31, '0'],
            [9, 31, '6'],
            [10, 31, '6'],
            [3, 32, '%'],
            [4, 32, '-'],
            [5, 32, '-'],
            [6, 32, '-'],
            [7, 32, '-'],
            [8, 32, '-'],
            [9, 32, '100%'],
            [10, 32, '100%'],
            [0, 33, 'Total'],
            [1, 33, 'Platform'],
            [2, 33, 'iOS'],
            [3, 33, 'Frequency'],
            [4, 33, '0'],
            [5, 33, '1'],
            [6, 33, '0'],
            [7, 33, '0'],
            [8, 33, '0'],
            [9, 33, '27'],
            [10, 33, '28'],
            [3, 34, '%'],
            [4, 34, '0%'],
            [5, 34, '1.2%'],
            [6, 34, '0%'],
            [7, 34, '0%'],
            [8, 34, '0%'],
            [9, 34, '33.75%'],
            [10, 34, '2.8%'],
            [2, 35, 'Linux'],
            [3, 35, 'Frequency'],
            [4, 35, '256'],
            [5, 35, '0'],
            [6, 35, '27'],
            [7, 35, '0'],
            [8, 35, '0'],
            [9, 35, '0'],
            [10, 35, '283'],
            [3, 36, '%'],
            [4, 36, '36.36%'],
            [5, 36, '0%'],
            [6, 36, '35.06%'],
            [7, 36, '0%'],
            [8, 36, '0%'],
            [9, 36, '0%'],
            [10, 36, '28.3%'],
            [2, 37, 'MacOSX'],
            [3, 37, 'Frequency'],
            [4, 37, '227'],
            [5, 37, '0'],
            [6, 37, '38'],
            [7, 37, '0'],
            [8, 37, '0'],
            [9, 37, '38'],
            [10, 37, '303'],
            [3, 38, '%'],
            [4, 38, '32.24%'],
            [5, 38, '0%'],
            [6, 38, '49.35%'],
            [7, 38, '0%'],
            [8, 38, '0%'],
            [9, 38, '47.5%'],
            [10, 38, '30.3%'],
            [2, 39, 'Windows'],
            [3, 39, 'Frequency'],
            [4, 39, '221'],
            [5, 39, '82'],
            [6, 39, '12'],
            [7, 39, '35'],
            [8, 39, '21'],
            [9, 39, '15'],
            [10, 39, '386'],
            [3, 40, '%'],
            [4, 40, '31.39%'],
            [5, 40, '98.8%'],
            [6, 40, '15.58%'],
            [7, 40, '100%'],
            [8, 40, '100%'],
            [9, 40, '18.75%'],
            [10, 40, '38.6%'],
            [2, 41, 'Total'],
            [3, 41, 'Frequency'],
            [4, 41, '704'],
            [5, 41, '83'],
            [6, 41, '77'],
            [7, 41, '35'],
            [8, 41, '21'],
            [9, 41, '80'],
            [10, 41, '1,000'],
            [3, 42, '%'],
            [4, 42, '100%'],
            [5, 42, '100%'],
            [6, 42, '100%'],
            [7, 42, '100%'],
            [8, 42, '100%'],
            [9, 42, '100%'],
            [10, 42, '100%']
        ];

        self::assertCrosstabHasExpectedCells($expectedCells, $crosstab);

        self::writeCrosstab($crosstab, __FUNCTION__);
    }

    /**
     * @return void
     */
    public function testBuildSimple(): void
    {
        $this->builder->setTitle('Browser Usage');
        $this->builder->setColVariableName(null);
        $crosstab = $this->builder->build();
        self::assertEquals(0, $crosstab->getDegreesOfFreedom());

        $matrix = $crosstab->getMatrix();
        self::assertCount(1, $matrix);
        self::assertCount(4, $matrix[0]);

        $expected = [
            [0, 0, 'Browser Usage'],
            [0, 1, 'Platform'],
            [1, 1, 'Frequency'],
            [2, 1, '%'],
            [0, 2, 'iOS'],
            [1, 2, '28'],
            [2, 2, '2.8%'],
            [0, 3, 'Linux'],
            [1, 3, '283'],
            [2, 3, '28.3%'],
            [0, 4, 'MacOSX'],
            [1, 4, '303'],
            [2, 4, '30.3%'],
            [0, 5, 'Windows'],
            [1, 5, '386'],
            [2, 5, '38.6%'],
            [0, 6, 'Total'],
            [1, 6, '1,000'],
            [2, 6, '100%']
        ];

        self::assertCrosstabHasExpectedCells($expected, $crosstab);

        self::writeCrosstab($crosstab, __FUNCTION__);
    }

    /**
     * @return void
     */
    public function testBuildWithNoRowVariables(): void
    {
        $this->builder->setRowVariableName(null);
        $this->expectExceptionMessage(CrosstabInvalidArgumentException::class);
        $this->expectExceptionMessage('No row variable provided');
        $this->builder->build();
    }

    /**
     * @return void
     */
    public function testBuildWithNothingToDisplay(): void
    {
        $this->builder->setShowPercent(false);
        $this->builder->setShowFrequency(false);
        $this->expectExceptionMessage(CrosstabInvalidArgumentException::class);
        $this->expectExceptionMessage('No frequency or percent to display in table');
        $this->builder->build();
    }

    /**
     * @return void
     */
    public function testBuildWithNoData(): void
    {
        $this->builder->setRawData([]);
        $crosstab = $this->builder->build();

        $expected = [
            [0, 0, 'Browser Usage By Platform'],
            [0, 1, 'There is no data to display'],
        ];

        self::assertCrosstabHasExpectedCells($expected, $crosstab);

        self::writeCrosstab($crosstab, __FUNCTION__);
    }

    /**
     * @return void
     */
    public function testBuildWithNoDataThatMatchesCategories(): void
    {
        $this->builder->setRawData([['foo' => 'bar']]);
        $crosstab = $this->builder->build();

        $expected = [
            [0, 0, 'Browser Usage By Platform'],
            [0, 1, 'There is no data to display'],
        ];

        self::assertCrosstabHasExpectedCells($expected, $crosstab);
    }
}
