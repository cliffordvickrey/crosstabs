<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit;

use CliffordVickrey\Crosstabs\Crosstab\Crosstab;
use CliffordVickrey\Crosstabs\CrosstabBuilder;
use CliffordVickrey\Crosstabs\Options\CrosstabPercentType;
use CliffordVickrey\Crosstabs\Tests\Provider\TestDataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function call_user_func;

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
        self::writeCrosstab($crosstab, __FUNCTION__);
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
        self::writeCrosstab($crosstab, __FUNCTION__);
    }
}
