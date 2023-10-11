<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Options;

use ArrayIterator;
use CliffordVickrey\Crosstabs\Exception\CrosstabUnexpectedValueException;
use CliffordVickrey\Crosstabs\Options\CrosstabOptions;
use CliffordVickrey\Crosstabs\Options\CrosstabPercentType;
use CliffordVickrey\Crosstabs\Options\CrosstabVariable;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(CrosstabOptions::class)]
class CrosstabOptionsTest extends TestCase
{
    /**
     * @return void
     */
    public function testToArray(): void
    {
        $layers = new CrosstabVariableCollection([new CrosstabVariable('layer name', 'layer description')]);

        $options = new CrosstabOptions();
        $options->setColVariableDescription('col description');
        $options->setColVariableName('col name');
        $options->setColVariableCategories([[
            'name' => 'col category name',
            'description' => 'col category description'
        ]]);
        $options->setKeyFrequency('N');
        $options->setKeyWeightedFrequency('weighted N');
        $options->setLayers($layers);
        $options->setLocale('fr_CA');
        $options->setMathematicalScale(14);
        $options->setMessageExpectedFrequency('expected frequency');
        $options->setMessageExpectedPercent('expected percent');
        $options->setMessageFrequency('frequency');
        $options->setMessageNil('nil');
        $options->setMessageNoData('no data');
        $options->setMessagePercent('percent');
        $options->setMessageTotal('total');
        $options->setMessageWeightedExpectedFrequency('weighted expected frequency');
        $options->setMessageWeightedExpectedPercent('weighted expected percent');
        $options->setMessageWeightedFrequency('weighted frequency');
        $options->setMessageWeightedPercent('setMessageWeightedPercent');
        $options->setPercentType(CrosstabPercentType::Row);
        $options->setRawData([['something' => 'something']]);
        $options->setRowVariableName('row name');
        $options->setRowVariableDescription('row description');
        $options->setRowVariableCategories([[
            'name' => 'row category name',
            'description' => 'row category description'
        ]]);
        $options->setScaleDecimal(2);
        $options->setScalePercent(4);
        $options->setShowExpectedFrequency(true);
        $options->setShowExpectedPercent(true);
        $options->setShowFrequency(true);
        $options->setShowPercent(true);
        $options->setShowWeightedExpectedFrequency(true);
        $options->setShowWeightedExpectedPercent(true);
        $options->setShowWeightedFrequency(true);
        $options->setShowWeightedPercent(true);
        $options->setTitle('title');

        $expected = [
            'colVariableDescription' => 'col description',
            'colVariableName' => 'col name',
            'colVariableCategories' => [[
                'name' => 'col category name',
                'description' => 'col category description'
            ]],
            'keyFrequency' => 'N',
            'keyWeightedFrequency' => 'weighted N',
            'layers' => [CrosstabVariable::__set_state([
                'description' => 'layer description',
                'name' => 'layer name',
                'categories' => []
            ])],
            'locale' => 'fr_CA',
            'mathematicalScale' => 14,
            'messageExpectedFrequency' => 'expected frequency',
            'messageExpectedPercent' => 'expected percent',
            'messageFrequency' => 'frequency',
            'messageNil' => 'nil',
            'messageNoData' => 'no data',
            'messagePercent' => 'percent',
            'messageTotal' => 'total',
            'messageWeightedExpectedFrequency' => 'weighted expected frequency',
            'messageWeightedExpectedPercent' => 'weighted expected percent',
            'messageWeightedFrequency' => 'weighted frequency',
            'messageWeightedPercent' => 'setMessageWeightedPercent',
            'percentType' => CrosstabPercentType::Row,
            'rawData' => [['something' => 'something']],
            'rowVariableDescription' => 'row description',
            'rowVariableName' => 'row name',
            'rowVariableCategories' => [[
                'name' => 'row category name',
                'description' => 'row category description',
            ]],
            'scaleDecimal' => 2,
            'scalePercent' => 4,
            'showExpectedFrequency' => true,
            'showExpectedPercent' => true,
            'showFrequency' => true,
            'showPercent' => true,
            'showWeightedExpectedFrequency' => true,
            'showWeightedExpectedPercent' => true,
            'showWeightedFrequency' => true,
            'showWeightedPercent' => true,
            'title' => 'title'
        ];

        self::assertEquals($expected, $options->toArray());
    }

    /**
     * @return void
     */
    public function testAddLayer(): void
    {
        $options = new CrosstabOptions();
        $options->addLayer('name', 'desc', [['name' => 'cat_name', 'description' => 'cat_description']]);

        $layer = CrosstabVariable::__set_state([
            'description' => 'desc',
            'name' => 'name',
            'categories' => [['name' => 'cat_name', 'description' => 'cat_description']]
        ]);

        self::assertEquals($layer, $options->toArray()['layers'][0] ?? null); // @phpstan-ignore-line

        $options = new CrosstabOptions();
        $options->addLayer(['name' => 'name', 'description' => 'desc']);

        $layer = CrosstabVariable::__set_state([
            'description' => 'desc',
            'name' => 'name'
        ]);

        self::assertEquals($layer, $options->toArray()['layers'][0] ?? null);  // @phpstan-ignore-line

        $options = new CrosstabOptions();
        $options->addLayer(['name' => 'name', 'description' => 'desc'], 'some_other_desc');

        $layer = CrosstabVariable::__set_state([
            'description' => 'some_other_desc',
            'name' => 'name'
        ]);

        self::assertEquals($layer, $options->toArray()['layers'][0] ?? null);  // @phpstan-ignore-line

        $this->expectException(CrosstabUnexpectedValueException::class);
        $this->expectExceptionMessage('Expected CrosstabLayer, array, or non-empty-string; got string');
        $options->addLayer('');
    }

    /**
     * @return void
     */
    public function testAddLayers(): void
    {
        $arr = [[
            'description' => 'desc',
            'name' => 'name',
            'categories' => [['name' => 'cat_name', 'description' => 'cat_description']]
        ]];

        $arrayIterator = new ArrayIterator($arr);

        $options = new CrosstabOptions();
        $options->addLayers($arrayIterator);

        $expected = [
            CrosstabVariable::__set_state([
                'description' => 'desc',
                'name' => 'name',
                'categories' => [['name' => 'cat_name', 'description' => 'cat_description']]
            ])
        ];

        self::assertEquals($expected, $options->toArray()['layers'] ?? null);

        $this->expectException(CrosstabUnexpectedValueException::class);
        $this->expectExceptionMessage('Expected non-empty-string; got string');
        $options->addLayers([new stdClass()]);
    }
}
