<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Helper;

use CliffordVickrey\Crosstabs\Helper\CrosstabVariableCollector;
use CliffordVickrey\Crosstabs\Options\CrosstabCategory;
use CliffordVickrey\Crosstabs\Options\CrosstabVariable;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use CliffordVickrey\Crosstabs\SourceData\CrosstabSourceDataCollection;
use CliffordVickrey\Crosstabs\Tests\Provider\TestDataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function call_user_func;

#[CoversClass(CrosstabVariableCollector::class)]
class CrosstabVariableCollectorTest extends TestCase
{
    /**
     * @var CrosstabSourceDataCollection
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private static CrosstabSourceDataCollection $sourceData;

    /**
     * @var CrosstabVariableCollector
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabVariableCollector $collector;

    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        $provider = new TestDataProvider();
        $rawData = call_user_func($provider);

        $emptyRow = [
            'Device Type' => '',
            'Browser' => '',
            'Platform' => '',
            'n' => '',
            'weight' => ''
        ];

        $rawData[] = $emptyRow;

        self::$sourceData = CrosstabSourceDataCollection::fromRawData($rawData);
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->collector = new CrosstabVariableCollector();
    }

    /**
     * @return void
     */
    public function testCollectVariables(): void
    {
        $vars = $this->collector->collectVariables(
            self::$sourceData,
            new CrosstabVariable('Platform'),
            new CrosstabVariable('Browser'),
            [new CrosstabVariable('Device Type'), []]
        );

        $expected = (new TestDataProvider())->getVariableCollection();

        self::assertEquals($expected, $vars);

        $vars = $this->collector->collectVariables(
            self::$sourceData,
            [],
            [
                'name' => 'Browser',
                'description' => 'Browser',
                'categories' => [new CrosstabCategory('IE', 'Internet Explorer')]
            ]
        );

        $expected = CrosstabVariableCollection::__set_state([
            'variables' => [
                CrosstabVariable::__set_state([
                    'description' => 'Browser',
                    'name' => 'Browser',
                    'categories' => [
                        CrosstabCategory::__set_state([
                            'description' => 'Internet Explorer',
                            'name' => 'IE',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Chrome',
                            'name' => 'Chrome',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Safari',
                            'name' => 'Safari',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Firefox',
                            'name' => 'Firefox',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Netscape',
                            'name' => 'Netscape',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Edge',
                            'name' => 'Edge',
                        ])
                    ]
                ])
            ]
        ]);

        self::assertEquals($expected, $vars);
    }
}
