<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Crosstab;

use CliffordVickrey\Crosstabs\Crosstab\CrosstabCellIndexDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrosstabCellIndexDto::class)]
class CrosstabCellIndexDtoTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstruct(): void
    {
        $dto = new CrosstabCellIndexDto(5, 10);
        self::assertEquals(5, $dto->rowIndex);
        self::assertEquals(10, $dto->cellIndex);
    }
}
