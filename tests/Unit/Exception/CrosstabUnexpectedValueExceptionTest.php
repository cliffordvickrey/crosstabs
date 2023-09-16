<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Exception;

use CliffordVickrey\Crosstabs\Exception\CrosstabUnexpectedValueException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(CrosstabUnexpectedValueException::class)]
class CrosstabUnexpectedValueExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function testFromValue(): void
    {
        $ex = CrosstabUnexpectedValueException::fromValue(new stdClass(), 'a rare spotted owl');
        self::assertEquals('Expected a rare spotted owl; got stdClass', $ex->getMessage());
    }
}
