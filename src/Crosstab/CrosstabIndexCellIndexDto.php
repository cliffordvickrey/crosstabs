<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Crosstab;

final readonly class CrosstabIndexCellIndexDto
{
    /**
     * @param int $rowIndex
     * @param int $cellIndex
     */
    public function __construct(public int $rowIndex, public int $cellIndex)
    {
    }
}
