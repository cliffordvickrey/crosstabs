<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Helper;

use CliffordVickrey\Crosstabs\Helper\CrosstabParamsSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrosstabParamsSerializer::class)]
class CrosstabParamsSerializerTest extends TestCase
{
    /**
     * @return void
     */
    public function testSerializeParams(): void
    {
        $params = [
            'foo' => 'bar',
            'bar' => 1,
            'baz' => null
        ];

        $obj = new CrosstabParamsSerializer();
        self::assertEquals('foo=bar&bar=1', $obj->serializeParams($params));
    }
}
