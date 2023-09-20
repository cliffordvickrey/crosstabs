<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Crosstab;

use ArrayIterator;
use CliffordVickrey\Crosstabs\Utilities\CrosstabMathUtilities;
use CliffordVickrey\Crosstabs\Writer\CrosstabHtmlWriter;
use CliffordVickrey\Crosstabs\Writer\CrosstabWriterInterface;
use IteratorAggregate;
use Stringable;
use Traversable;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function is_array;
use function is_object;
use function iterator_to_array;

/**
 * @implements IteratorAggregate<int, CrosstabRow>
 */
class Crosstab implements CrosstabInterface, IteratorAggregate, Stringable
{
    /** @var array<int, CrosstabRow> */
    protected array $rows;
    /** @var list<list<CrosstabDataItem>> */
    protected array $matrix;

    /**
     * @param array<int, CrosstabRow|array<int, CrosstabCell>> $rows
     * @param list<list<CrosstabDataItem>> $matrix
     */
    public function __construct(array $rows = [], array $matrix = [])
    {
        $this->hydrate(['rows' => $rows, 'matrix' => $matrix]);
    }

    /**
     * @param array<array-key, mixed> $data
     * @return void
     * @psalm-suppress MixedAssignment
     */
    private function hydrate(array $data): void
    {
        $rawRows = $data['rows'] ?? [];

        if (!is_array($rawRows)) {
            $rawRows = [];
        }

        $rows = [];

        foreach ($rawRows as $i => $rawRow) {
            $rows[(int)$i] = self::parseRow($rawRow);
        }

        $this->rows = $rows;

        $rawMatrix = $data['matrix'] ?? [];

        if ($rawMatrix instanceof Traversable) {
            $rawMatrix = iterator_to_array($rawMatrix);
        }

        if (!is_array($rawMatrix)) {
            $rawMatrix = [];
        }

        $rawMatrix = array_values($rawMatrix);

        $matrix = [];

        foreach ($rawMatrix as $i => $matrixRow) {
            $matrix[$i] = self::parseMatrixRow($matrixRow);
        }

        /** @psalm-suppress PropertyTypeCoercion */
        $this->matrix = $matrix;
    }

    /**
     * @param mixed $val
     * @return CrosstabRow
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    private static function parseRow(mixed $val): CrosstabRow
    {
        if ($val instanceof CrosstabRow) {
            return $val;
        }

        if (is_array($val)) {
            return new CrosstabRow(array_filter(array_map(
                static fn($cell) => ($cell instanceof CrosstabCell)
                    ? $cell
                    : (is_array($cell) ? CrosstabCell::__set_state($cell) : null),
                $val
            )));
        }

        return new CrosstabRow();
    }

    /**
     * @param mixed $row
     * @return list<CrosstabDataItem>
     */
    private static function parseMatrixRow(mixed $row): array
    {
        if ($row instanceof Traversable) {
            $row = iterator_to_array($row);
        }

        if (!is_array($row)) {
            return [];
        }

        return array_values(array_map(self::parseDataItem(...), $row));
    }

    /**
     * @param non-empty-string $message
     * @param non-empty-string|null $title
     * @return self
     */
    public static function withoutData(string $message, ?string $title = null): self
    {
        $rows = [];

        if (null !== $title) {
            $rows[] = [CrosstabCell::header($title)];
        }

        $rows[] = [CrosstabCell::dataCell($message)];

        return new self($rows);
    }

    /**
     * @param mixed $val
     * @return CrosstabDataItem
     */
    private static function parseDataItem(mixed $val): CrosstabDataItem
    {
        if ($val instanceof CrosstabDataItem) {
            return $val;
        }

        if (is_object($val)) {
            $val = (array)$val;
        }

        if (is_array($val)) {
            return CrosstabDataItem::__set_state($val);
        }

        return CrosstabDataItem::__set_state([]);
    }

    /**
     * Gets a cell at specified Cartesian coordinates. If none exists, return NULL
     * @param int $x
     * @param int $y
     * @return CrosstabCell|null
     * @todo something better than this
     */
    public function getCell(int $x, int $y): ?CrosstabCell
    {
        $rowspanLookAhead = [];

        foreach ($this->rows as $currentY => $row) {
            if ($currentY > $y) {
                return null;
            }

            $currentX = 0;

            foreach ($row as $cell) {
                $valid = false;

                while (!$valid) {
                    $valid = ($rowspanLookAhead[$currentX] ?? 0) < 1;

                    if (!$valid) {
                        $rowspanLookAhead[$currentX]--;
                        $currentX++;
                    }
                }

                if ($cell->rowspan > 1) {
                    for ($i = 0; $i < $cell->colspan; $i++) {
                        $rowspanLookAhead[$currentX + $i] = $cell->rowspan - 1;
                    }
                }

                if ($currentX === $x && $currentY === $y) {
                    return $cell;
                }

                $currentX += $cell->colspan;
            }
        }

        return null;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        foreach ($this->rows as $i => $row) {
            $this->rows[$i] = clone $row;
        }

        foreach ($this->matrix as $y => $matrixRow) {
            foreach ($matrixRow as $x => $dataItem) {
                /** @psalm-suppress PropertyTypeCoercion */
                $this->matrix[$y][$x] = clone $dataItem;
            }
        }
    }

    /**
     * Gets a rectangular matrix of value objects, representing data within the crosstab
     * @return list<list<CrosstabDataItem>>
     */
    public function getMatrix(): array
    {
        return $this->matrix;
    }

    /**
     * Gets the number of independent values used to compute a chi-squared test statistic, etc.
     * @return int
     */
    public function getDegreesOfFreedom(): int
    {
        return (count($this->matrix) - 1) * (count($this->matrix[0] ?? []) - 1);
    }

    /**
     * Gets the chi-squared test statistic. The higher the value, and the lower the number of cells used as factors to
     * compute the statistic, the more likely there is to be a relationship between the row and column variables'
     * population parameters
     * @param bool $weighted Whether to use weighted values
     * @param int<1, max>|null $scale Floating-point scale to use
     * @return float
     */
    public function getChiSquared(bool $weighted = false, ?int $scale = null): float
    {
        if (null === $scale) {
            $scale = CrosstabMathUtilities::DEFAULT_SCALE;
        }

        $chiSquared = 0.0;

        foreach ($this->matrix as $dataItems) {
            foreach ($dataItems as $dataItem) {
                $n = $weighted ? $dataItem->weightedFrequency : $dataItem->frequency;
                $expected = $weighted ? $dataItem->weightedExpectedFrequency : $dataItem->expectedFrequency;

                $difference = CrosstabMathUtilities::subtract($n, $expected, $scale);
                $differenceSquared = CrosstabMathUtilities::pow($difference, 2, $scale);
                $quantity = CrosstabMathUtilities::divide($differenceSquared, $expected, $scale);
                $chiSquared = CrosstabMathUtilities::add($chiSquared, $quantity, $scale);
            }
        }

        return $chiSquared;
    }

    /**
     * @return ArrayIterator<int, CrosstabRow>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->rows);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->rows);
    }

    /**
     * @return array{rows: array<int, CrosstabRow>, matrix: list<list<CrosstabDataItem>>}
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array{rows: array<int, CrosstabRow>, matrix: list<list<CrosstabDataItem>>}
     */
    public function toArray(): array
    {
        return ['rows' => $this->rows, 'matrix' => $this->matrix];
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->hydrate($data);
    }

    /**
     * @return array{rows: array<int, CrosstabRow>, matrix: list<list<CrosstabDataItem>>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @inheritDoc
     */
    public function writeToFile(
        ?string $filename = null,
        array $options = [],
        ?CrosstabWriterInterface $writer = null
    ): string {
        if (null === $writer) {
            $writer = new CrosstabHtmlWriter();
        }

        return $writer->writeToFile($this, $filename, $options);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->write();
    }

    /**
     * @inheritDoc
     */
    public function write(array $options = [], ?CrosstabWriterInterface $writer = null): string
    {
        if (null === $writer) {
            $writer = new CrosstabHtmlWriter();
        }

        return $writer->write($this, $options);
    }
}
