<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Provider;

use CliffordVickrey\Crosstabs\Crosstab\Crosstab;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabCell;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabCellIndexDto;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabDataItem;
use CliffordVickrey\Crosstabs\Crosstab\CrosstabRow;
use CliffordVickrey\Crosstabs\CrosstabBuilder;
use CliffordVickrey\Crosstabs\Exception\CrosstabUnexpectedValueException;
use CliffordVickrey\Crosstabs\Options\CrosstabCategory;
use CliffordVickrey\Crosstabs\Options\CrosstabPercentType;
use CliffordVickrey\Crosstabs\Options\CrosstabVariable;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use RuntimeException;

use function array_combine;
use function array_map;
use function call_user_func;
use function fclose;
use function feof;
use function fgetcsv;
use function file_get_contents;
use function file_put_contents;
use function fopen;
use function is_file;
use function serialize;
use function unserialize;

class TestDataProvider
{
    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(): array
    {
        $filename = __DIR__ . '/../resources/test-data.csv';

        $resource = fopen($filename, 'r');

        if (false === $resource) {
            throw new RuntimeException("Could not open $filename for writing");
        }

        $headings = null;
        $rows = [];

        while (false !== ($row = fgetcsv($resource))) {
            if (null === $headings) {
                $headings = array_map(strval(...), $row);
                continue;
            }

            /** @var array<string, mixed> $row */
            $row = array_combine($headings, $row);
            $rows[] = $row;
        }

        if (!feof($resource)) {
            throw new RuntimeException("Could fully read through $filename");
        }

        fclose($resource);

        return $rows;
    }

    /**
     * @return CrosstabVariableCollection
     */
    public function getVariableCollection(): CrosstabVariableCollection
    {
        return CrosstabVariableCollection::__set_state(['variables' => [
            CrosstabVariable::__set_state([
                'description' => 'Device Type',
                'name' => 'Device Type',
                'categories' =>
                    [
                        CrosstabCategory::__set_state([
                            'description' => 'Desktop',
                            'name' => 'Desktop',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Mobile Device',
                            'name' => 'Mobile Device',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Tablet',
                            'name' => 'Tablet',
                        ]),
                    ],
            ]),
            CrosstabVariable::__set_state([
                'description' => 'Platform',
                'name' => 'Platform',
                'categories' =>
                    [

                        CrosstabCategory::__set_state([
                            'description' => 'iOS',
                            'name' => 'iOS',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Linux',
                            'name' => 'Linux',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'MacOSX',
                            'name' => 'MacOSX',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Windows',
                            'name' => 'Windows',
                        ]),
                    ],
            ]),
            CrosstabVariable::__set_state([
                'description' => 'Browser',
                'name' => 'Browser',
                'categories' =>
                    [
                        CrosstabCategory::__set_state([
                            'description' => 'Chrome',
                            'name' => 'Chrome',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Edge',
                            'name' => 'Edge',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Firefox',
                            'name' => 'Firefox',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'IE',
                            'name' => 'IE',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Netscape',
                            'name' => 'Netscape',
                        ]),
                        CrosstabCategory::__set_state([
                            'description' => 'Safari',
                            'name' => 'Safari',
                        ]),
                    ],
            ]),
        ]]);
    }

    /**
     * @return Crosstab
     */
    public function getCrosstab(): Crosstab
    {
        $filename = __DIR__ . '/../resources/crosstab.txt';

        if (!is_file($filename)) {
            $builder = new CrosstabBuilder();
            $builder->setColVariableName('Browser');
            $builder->setRowVariableName('Platform');
            $builder->setTitle('Browser Usage By Platform');
            $builder->setShowPercent(true);
            $builder->setPercentType(CrosstabPercentType::ColumnWithinLayer);
            $builder->setRawData(call_user_func($this));
            $crosstab = $builder->build();
            file_put_contents($filename, serialize($crosstab));
        }

        $contents = file_get_contents($filename);

        if (false === $contents) {
            throw new RuntimeException("Could not open $filename for reading");
        }

        $obj = unserialize($contents, ['allowed_classes' => [
            Crosstab::class,
            CrosstabCell::class,
            CrosstabDataItem::class,
            CrosstabCellIndexDto::class,
            CrosstabRow::class
        ]]);

        if (!($obj instanceof Crosstab)) {
            throw CrosstabUnexpectedValueException::fromValue($obj, Crosstab::class);
        }

        return $obj;
    }
}
