<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Helper;

use CliffordVickrey\Crosstabs\Exception\CrosstabInvalidArgumentException;
use CliffordVickrey\Crosstabs\Helper\CrosstabTabulator;
use CliffordVickrey\Crosstabs\Helper\CrosstabTreeBuilder;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use CliffordVickrey\Crosstabs\SourceData\CrosstabSourceDataCollection;
use CliffordVickrey\Crosstabs\Tests\Provider\TestDataProvider;
use CliffordVickrey\Crosstabs\Tree\CrosstabTreeCategoryNode;
use CliffordVickrey\Crosstabs\Tree\CrosstabTreeDataItemNode;
use CliffordVickrey\Crosstabs\Tree\CrosstabTreeVariableNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function call_user_func;
use function sprintf;

#[CoversClass(CrosstabTreeBuilder::class)]
class CrosstabTreeBuilderTest extends TestCase
{
    /**
     * @var CrosstabVariableCollection
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private static CrosstabVariableCollection $variables;

    /**
     * @var array{n: array<string, float>, weightedN: array<string, float>}
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private static array $totals;

    /**
     * @var CrosstabTreeBuilder
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabTreeBuilder $treeBuilder;

    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        $provider = new TestDataProvider();
        $rawData = call_user_func($provider);

        self::$variables = $provider->getVariableCollection();

        $tabulator = new CrosstabTabulator();
        $sourceData = CrosstabSourceDataCollection::fromRawData($rawData, 'n', 'weight');
        self::$totals = $tabulator->tabulate(self::$variables, $sourceData);
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->treeBuilder = new CrosstabTreeBuilder();
    }

    /**
     * @return void
     */
    public function testBuildTree(): void
    {
        $tree = $this->treeBuilder->buildTree(self::$variables, self::$totals);
        self::assertCount(3, $tree);

        $expected = [
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [256.0, 0.256],
            [0.0, 0.0],
            [27.0, 0.027],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [283.0, 0.283],
            [227.0, 0.227],
            [0.0, 0.0],
            [38.0, 0.038],
            [0.0, 0.0],
            [0.0, 0.0],
            [38.0, 0.038],
            [303.0, 0.303],
            [221.0, 0.221],
            [82.0, 0.082],
            [12.0, 0.012],
            [35.0, 0.035],
            [21.0, 0.021],
            [15.0, 0.015],
            [386.0, 0.386],
            [704.0, 0.704],
            [82.0, 0.082],
            [77.0, 0.077],
            [35.0, 0.035],
            [21.0, 0.021],
            [53.0, 0.053],
            [972.0, 0.972],
            [0.0, 0.0],
            [1.0, 0.001],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [21.0, 0.021],
            [22.0, 0.022],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [1.0, 0.001],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [21.0, 0.021],
            [22.0, 0.022],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [6.0, 0.006],
            [6.0, 0.006],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [6.0, 0.006],
            [6.0, 0.006],
            [0.0, 0.0],
            [1.0, 0.001],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [27.0, 0.027],
            [28.0, 0.028],
            [256.0, 0.256],
            [0.0, 0.0],
            [27.0, 0.027],
            [0.0, 0.0],
            [0.0, 0.0],
            [0.0, 0.0],
            [283.0, 0.283],
            [227.0, 0.227],
            [0.0, 0.0],
            [38.0, 0.038],
            [0.0, 0.0],
            [0.0, 0.0],
            [38.0, 0.038],
            [303.0, 0.303],
            [221.0, 0.221],
            [82.0, 0.082],
            [12.0, 0.012],
            [35.0, 0.035],
            [21.0, 0.021],
            [15.0, 0.015],
            [386.0, 0.386],
            [704.0, 0.704],
            [83.0, 0.083],
            [77.0, 0.077],
            [35.0, 0.035],
            [21.0, 0.021],
            [80.0, 0.08],
            [1000.0, 1.0]
        ];

        $index = -1;

        $varCount = 0;
        $categoryCount = 0;

        foreach ($tree as $node) {
            if ($node instanceof CrosstabTreeVariableNode) {
                $varCount++;
                continue;
            }

            if ($node instanceof CrosstabTreeCategoryNode) {
                $categoryCount++;
                continue;
            }

            if (!($node instanceof CrosstabTreeDataItemNode)) {
                self::fail('Expected node to be a data leaf');
            }

            list($expectedFrequency, $expectedPercent) = $expected[++$index];

            $msg = sprintf(
                'Expected data node %d to have a frequency of %g; got %g',
                $index,
                $expectedFrequency,
                (float)$node->payload->frequency
            );

            self::assertEquals($expectedFrequency, $node->payload->frequency, $msg);

            $msg = sprintf(
                'Expected data node %d to have a percentage of %g; got %g',
                $index,
                $expectedPercent,
                (float)$node->payload->percent
            );

            self::assertEquals($expectedPercent, $node->payload->percent, $msg);
        }

        self::assertEquals(25, $varCount);
        self::assertEquals(164, $categoryCount);
    }

    /**
     * @return void
     */
    public function testBuildTreeInvalid(): void
    {
        $this->expectException(CrosstabInvalidArgumentException::class);
        $this->expectExceptionMessage('Variable collection cannot be empty');
        $this->treeBuilder->buildTree(new CrosstabVariableCollection([]), self::$totals);
    }

    /**
     * @return void
     */
    public function testGetMatrix(): void
    {
        $tree = $this->treeBuilder->buildTree(self::$variables, self::$totals);
        $matrix = $this->treeBuilder->getMatrix($tree);
        self::assertCount(12, $matrix);
    }
}
