<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Exception;

use CliffordVickrey\Crosstabs\Exception\CrosstabOutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrosstabOutOfBoundsException::class)]
class CrosstabOutOfBoundsExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testFromValue(): void
    {
        $ex = CrosstabOutOfBoundsException::fromIllegalOffset('the lands between');
        self::assertEquals('Illegal offset, "the lands between"', $ex->getMessage());
    }
}
