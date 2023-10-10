<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Options;

use ArrayIterator;
use CliffordVickrey\Crosstabs\Exception\CrosstabUnexpectedValueException;
use CliffordVickrey\Crosstabs\Options\CrosstabCategory;
use CliffordVickrey\Crosstabs\Options\CrosstabVariable;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(CrosstabVariableCollection::class)]
class CrosstabVariableCollectionTest extends TestCase
{
    /**
     * @return void
     */
    public function testSetState(): void
    {
        $collection = CrosstabVariableCollection::__set_state(['variables' => [new CrosstabVariable('test')]]);
        self::assertEquals('test', $collection->toArray()[0]->name);

        $collection = CrosstabVariableCollection::__set_state(['variables' => ['test']]);
        self::assertEquals('test', $collection->toArray()[0]->name);

        $collection = CrosstabVariableCollection::__set_state(['variables' => [['name' => 'test']]]);
        self::assertEquals('test', $collection->toArray()[0]->name);

        $obj = new stdClass();
        $obj->name = 'test';
        $collection = CrosstabVariableCollection::__set_state([$obj]);
        self::assertEquals('test', $collection->toArray()[0]->name);

        $collection = CrosstabVariableCollection::__set_state(['variables' => [new ArrayIterator(['name' => 'test'])]]);
        self::assertEquals('test', $collection->toArray()[0]->name);

        $this->expectException(CrosstabUnexpectedValueException::class);
        $this->expectExceptionMessage('Expected non-empty-string, object, array, or Traversable; got bool');
        CrosstabVariableCollection::__set_state(['variables' => [false]]);
    }

    /**
     * @return void
     */
    public function testIsValid(): void
    {
        $collection = new CrosstabVariableCollection([]);
        self::assertFalse($collection->isValid());

        $collection = new CrosstabVariableCollection([new CrosstabVariable('var')]);
        self::assertFalse($collection->isValid());

        $cats = [new CrosstabCategory('cat1')];
        $collection = new CrosstabVariableCollection([new CrosstabVariable('var', categories: $cats)]);
        self::assertTrue($collection->isValid());
    }

    /**
     * @return void
     */
    public function testGetIterator(): void
    {
        $collection = new CrosstabVariableCollection([new CrosstabVariable('var1'), new CrosstabVariable('var2')]);

        $k = 0;

        foreach ($collection as $var) {
            self::assertInstanceOf(CrosstabVariable::class, $var);
            $k++;
        }

        self::assertCount($k, $collection);
    }
}
