<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Options;

use ArrayIterator;
use CliffordVickrey\Crosstabs\Exception\CrosstabUnexpectedValueException;
use CliffordVickrey\Crosstabs\Options\CrosstabCategory;
use CliffordVickrey\Crosstabs\Options\CrosstabVariable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(CrosstabVariable::class)]
class CrosstabVariableTest extends TestCase
{
    /**
     * @return void
     */
    public function testSetState(): void
    {
        $variable = CrosstabVariable::__set_state([
            'name' => 'test'
        ]);

        self::assertEquals('test', $variable->name);
        self::assertEquals('test', $variable->description);
        self::assertEquals([], $variable->categories);

        $variable = CrosstabVariable::__set_state([
            'name' => 'test',
            'description' => 'test description'
        ]);

        self::assertEquals('test', $variable->name);
        self::assertEquals('test description', $variable->description);
        self::assertEquals([], $variable->categories);

        $variable = CrosstabVariable::__set_state([
            'name' => 'test',
            'categories' => [new CrosstabCategory('cat1')]
        ]);

        self::assertEquals('cat1', $variable->categories[0]->name);

        $variable = CrosstabVariable::__set_state([
            'name' => 'test',
            'categories' => ['cat1']
        ]);

        self::assertEquals('cat1', $variable->categories[0]->name);

        $obj = new stdClass();
        $obj->name = 'cat1';

        $variable = CrosstabVariable::__set_state([
            'name' => 'test',
            'categories' => [$obj]
        ]);

        self::assertEquals('cat1', $variable->categories[0]->name);

        $variable = CrosstabVariable::__set_state([
            'name' => 'test',
            'categories' => [new ArrayIterator(['name' => 'cat1'])]
        ]);

        self::assertEquals('cat1', $variable->categories[0]->name);


        $this->expectException(CrosstabUnexpectedValueException::class);
        $this->expectExceptionMessage('Expected non-empty-string, object, array, or Traversable; got bool');
        CrosstabVariable::__set_state([
            'name' => 'test',
            'categories' => [false]
        ]);
    }
}
