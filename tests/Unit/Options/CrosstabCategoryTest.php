<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Options;

use CliffordVickrey\Crosstabs\Options\CrosstabCategory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrosstabCategory::class)]
class CrosstabCategoryTest extends TestCase
{
    /**
     * @return void
     */
    public function testSetState(): void
    {
        $category = CrosstabCategory::__set_state(['name' => 'test']);
        self::assertEquals('test', $category->name);
        self::assertEquals('test', $category->description);

        $category = CrosstabCategory::__set_state(['name' => 'test', 'description' => 'test description']);
        self::assertEquals('test', $category->name);
        self::assertEquals('test description', $category->description);
    }
}
