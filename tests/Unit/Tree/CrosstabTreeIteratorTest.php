<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Tree;

use CliffordVickrey\Crosstabs\Exception\CrosstabOutOfBoundsException;
use CliffordVickrey\Crosstabs\Options\CrosstabVariable;
use CliffordVickrey\Crosstabs\Tree\CrosstabTreeIterator;
use CliffordVickrey\Crosstabs\Tree\CrosstabTreeVariableNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrosstabTreeIterator::class)]
class CrosstabTreeIteratorTest extends TestCase
{
    /**
     * @var CrosstabTreeIterator
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabTreeIterator $it;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $nodes = [
            new CrosstabTreeVariableNode(new CrosstabVariable('no_children')),
            new CrosstabTreeVariableNode(new CrosstabVariable('with_children'), children: [
                new CrosstabTreeVariableNode(new CrosstabVariable('child'))
            ])
        ];

        $this->it = new CrosstabTreeIterator($nodes);
    }

    /**
     * @return void
     */
    public function testCurrent(): void
    {
        foreach ($this->it as $i => $node) {
            self::assertEquals($i, $this->it->key());
            self::assertInstanceOf(CrosstabTreeVariableNode::class, $node);
        }

        self::assertNull($this->it->key());

        $this->expectException(CrosstabOutOfBoundsException::class);
        $this->expectExceptionMessage('Iterator is invalid');
        $this->it->current();
    }

    /**
     * @return void
     */
    public function testGetChildren(): void
    {
        $this->it->rewind();
        self::assertNull($this->it->getChildren());

        $this->it->next();
        self::assertInstanceOf(CrosstabTreeIterator::class, $this->it->getChildren());

        $this->it->next();
        self::assertFalse($this->it->valid());
        self::assertNull($this->it->getChildren());
    }
}
