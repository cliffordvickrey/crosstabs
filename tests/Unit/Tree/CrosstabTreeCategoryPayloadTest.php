<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Tree;

use CliffordVickrey\Crosstabs\Options\CrosstabCategory;
use CliffordVickrey\Crosstabs\Tree\CrosstabTreeCategoryPayload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrosstabTreeCategoryPayload::class)]
class CrosstabTreeCategoryPayloadTest extends TestCase
{
    /**
     * @return void
     */
    public function testConstruct(): void
    {
        $payload = new CrosstabTreeCategoryPayload(new CrosstabCategory('test'), true);
        self::assertEquals('test', $payload->category->name);
        self::assertTrue($payload->isTotal);
    }
}
