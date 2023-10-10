<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Helper;

use CliffordVickrey\Crosstabs\Exception\CrosstabLogicException;
use CliffordVickrey\Crosstabs\Helper\CrosstabTabulator;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use CliffordVickrey\Crosstabs\SourceData\CrosstabSourceDataCollection;
use CliffordVickrey\Crosstabs\Tests\Provider\TestDataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function call_user_func;

#[CoversClass(CrosstabTabulator::class)]
class CrosstabTabulatorTest extends TestCase
{
    /**
     * @var CrosstabSourceDataCollection
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private static CrosstabSourceDataCollection $sourceData;

    /**
     * @var CrosstabVariableCollection
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private static CrosstabVariableCollection $variables;

    /**
     * @var CrosstabTabulator
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabTabulator $tabulator;

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

        self::$sourceData = CrosstabSourceDataCollection::fromRawData($rawData, 'n', 'weight');
        self::$variables = $provider->getVariableCollection();
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->tabulator = new CrosstabTabulator();
    }

    /**
     * @return void
     */
    public function testTabulate(): void
    {
        $totals = $this->tabulator->tabulate(self::$variables, self::$sourceData);

        $expected = [
            'n' => [
                '' => 1000.0,
                'Device+Type=Desktop' => 972.0,
                'Platform=Linux' => 283.0,
                'Device+Type=Desktop&Platform=Linux' => 283.0,
                'Browser=Chrome' => 704.0,
                'Device+Type=Desktop&Browser=Chrome' => 704.0,
                'Platform=Linux&Browser=Chrome' => 256.0,
                'Device+Type=Desktop&Platform=Linux&Browser=Chrome' => 256.0,
                'Device+Type=Tablet' => 6.0,
                'Platform=iOS' => 28.0,
                'Device+Type=Tablet&Platform=iOS' => 6.0,
                'Browser=Safari' => 80.0,
                'Device+Type=Tablet&Browser=Safari' => 6.0,
                'Platform=iOS&Browser=Safari' => 27.0,
                'Device+Type=Tablet&Platform=iOS&Browser=Safari' => 6.0,
                'Platform=MacOSX' => 303.0,
                'Device+Type=Desktop&Platform=MacOSX' => 303.0,
                'Platform=MacOSX&Browser=Chrome' => 227.0,
                'Device+Type=Desktop&Platform=MacOSX&Browser=Chrome' => 227.0,
                'Platform=Windows' => 386.0,
                'Device+Type=Desktop&Platform=Windows' => 386.0,
                'Browser=IE' => 35.0,
                'Device+Type=Desktop&Browser=IE' => 35.0,
                'Platform=Windows&Browser=IE' => 35.0,
                'Device+Type=Desktop&Platform=Windows&Browser=IE' => 35.0,
                'Platform=Windows&Browser=Chrome' => 221.0,
                'Device+Type=Desktop&Platform=Windows&Browser=Chrome' => 221.0,
                'Browser=Firefox' => 77.0,
                'Device+Type=Desktop&Browser=Firefox' => 77.0,
                'Platform=MacOSX&Browser=Firefox' => 38.0,
                'Device+Type=Desktop&Platform=MacOSX&Browser=Firefox' => 38.0,
                'Device+Type=Mobile+Device' => 22.0,
                'Device+Type=Mobile+Device&Platform=iOS' => 22.0,
                'Device+Type=Mobile+Device&Browser=Safari' => 21.0,
                'Device+Type=Mobile+Device&Platform=iOS&Browser=Safari' => 21.0,
                'Browser=Netscape' => 21.0,
                'Device+Type=Desktop&Browser=Netscape' => 21.0,
                'Platform=Windows&Browser=Netscape' => 21.0,
                'Device+Type=Desktop&Platform=Windows&Browser=Netscape' => 21.0,
                'Device+Type=Desktop&Browser=Safari' => 53.0,
                'Platform=MacOSX&Browser=Safari' => 38.0,
                'Device+Type=Desktop&Platform=MacOSX&Browser=Safari' => 38.0,
                'Browser=Edge' => 83.0,
                'Device+Type=Mobile+Device&Browser=Edge' => 1.0,
                'Platform=iOS&Browser=Edge' => 1.0,
                'Device+Type=Mobile+Device&Platform=iOS&Browser=Edge' => 1.0,
                'Platform=Windows&Browser=Safari' => 15.0,
                'Device+Type=Desktop&Platform=Windows&Browser=Safari' => 15.0,
                'Platform=Linux&Browser=Firefox' => 27.0,
                'Device+Type=Desktop&Platform=Linux&Browser=Firefox' => 27.0,
                'Platform=Windows&Browser=Firefox' => 12.0,
                'Device+Type=Desktop&Platform=Windows&Browser=Firefox' => 12.0,
                'Device+Type=Desktop&Browser=Edge' => 82.0,
                'Platform=Windows&Browser=Edge' => 82.0,
                'Device+Type=Desktop&Platform=Windows&Browser=Edge' => 82.0
            ],
            'weightedN' => [
                '' => 981.2079,
                'Device+Type=Desktop' => 947.5421,
                'Platform=Linux' => 220.8837,
                'Device+Type=Desktop&Platform=Linux' => 220.8837,
                'Browser=Chrome' => 689.9674,
                'Device+Type=Desktop&Browser=Chrome' => 689.9674,
                'Platform=Linux&Browser=Chrome' => 192.4608,
                'Device+Type=Desktop&Platform=Linux&Browser=Chrome' => 192.4608,
                'Device+Type=Tablet' => 6.8412,
                'Platform=iOS' => 33.6658,
                'Device+Type=Tablet&Platform=iOS' => 6.8412,
                'Browser=Safari' => 82.1491,
                'Device+Type=Tablet&Browser=Safari' => 6.8412,
                'Platform=iOS&Browser=Safari' => 32.7027,
                'Device+Type=Tablet&Platform=iOS&Browser=Safari' => 6.8412,
                'Platform=MacOSX' => 312.5575,
                'Device+Type=Desktop&Platform=MacOSX' => 312.5575,
                'Platform=MacOSX&Browser=Chrome' => 233.7873,
                'Device+Type=Desktop&Platform=MacOSX&Browser=Chrome' => 233.7873,
                'Platform=Windows' => 414.1009,
                'Device+Type=Desktop&Platform=Windows' => 414.1009,
                'Browser=IE' => 36.372,
                'Device+Type=Desktop&Browser=IE' => 36.372,
                'Platform=Windows&Browser=IE' => 36.372,
                'Device+Type=Desktop&Platform=Windows&Browser=IE' => 36.372,
                'Platform=Windows&Browser=Chrome' => 263.7193,
                'Device+Type=Desktop&Platform=Windows&Browser=Chrome' => 263.7193,
                'Browser=Firefox' => 85.5639,
                'Device+Type=Desktop&Browser=Firefox' => 85.5639,
                'Platform=MacOSX&Browser=Firefox' => 42.8298,
                'Device+Type=Desktop&Platform=MacOSX&Browser=Firefox' => 42.8298,
                'Device+Type=Mobile+Device' => 26.8246,
                'Device+Type=Mobile+Device&Platform=iOS' => 26.8246,
                'Device+Type=Mobile+Device&Browser=Safari' => 25.8615,
                'Device+Type=Mobile+Device&Platform=iOS&Browser=Safari' => 25.8615,
                'Browser=Netscape' => 18.5178,
                'Device+Type=Desktop&Browser=Netscape' => 18.5178,
                'Platform=Windows&Browser=Netscape' => 18.5178,
                'Device+Type=Desktop&Platform=Windows&Browser=Netscape' => 18.5178,
                'Device+Type=Desktop&Browser=Safari' => 49.4464,
                'Platform=MacOSX&Browser=Safari' => 35.9404,
                'Device+Type=Desktop&Platform=MacOSX&Browser=Safari' => 35.9404,
                'Browser=Edge' => 68.6377,
                'Device+Type=Mobile+Device&Browser=Edge' => 0.9631,
                'Platform=iOS&Browser=Edge' => 0.9631,
                'Device+Type=Mobile+Device&Platform=iOS&Browser=Edge' => 0.9631,
                'Platform=Windows&Browser=Safari' => 13.506,
                'Device+Type=Desktop&Platform=Windows&Browser=Safari' => 13.506,
                'Platform=Linux&Browser=Firefox' => 28.4229,
                'Device+Type=Desktop&Platform=Linux&Browser=Firefox' => 28.4229,
                'Platform=Windows&Browser=Firefox' => 14.3112,
                'Device+Type=Desktop&Platform=Windows&Browser=Firefox' => 14.3112,
                'Device+Type=Desktop&Browser=Edge' => 67.6746,
                'Platform=Windows&Browser=Edge' => 67.6746,
                'Device+Type=Desktop&Platform=Windows&Browser=Edge' => 67.6746
            ]
        ];

        self::assertEquals($expected, $totals);

        $this->expectException(CrosstabLogicException::class);
        $this->expectExceptionMessage('Variables cannot be empty');
        $this->tabulator->tabulate(new CrosstabVariableCollection([]), self::$sourceData);
    }
}
