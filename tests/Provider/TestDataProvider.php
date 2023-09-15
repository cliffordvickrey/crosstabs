<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Provider;

use RuntimeException;

use function array_combine;
use function array_map;
use function fclose;
use function feof;
use function fgetcsv;
use function fopen;

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
}
