<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Tree;

use CliffordVickrey\Crosstabs\Exception\CrosstabOutOfBoundsException;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
use CliffordVickrey\Crosstabs\Tests\Provider\TestDataProvider;
use CliffordVickrey\Crosstabs\Tree\CrosstabTree;
use CliffordVickrey\Crosstabs\Tree\CrosstabTreeIterator;
use CliffordVickrey\Crosstabs\Tree\CrosstabTreeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function count;
use function max;

#[CoversClass(CrosstabTree::class)]
class CrosstabTreeTest extends TestCase
{
    /**
     * @var CrosstabTree
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabTree $tree;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $provider = new TestDataProvider();
        $this->tree = new CrosstabTree($provider->getVariableCollection());
    }

    /**
     * @return void
     */
    public function testGetFirstAndLastVariablesInTree(): void
    {
        list ($first, $last) = $this->tree->getFirstAndLastVariablesInTree();
        self::assertEquals('Device Type', $first->name);
        self::assertEquals('Browser', $last->name);

        $tree = new CrosstabTree(new CrosstabVariableCollection([]));

        $this->expectException(CrosstabOutOfBoundsException::class);
        $this->expectExceptionMessage('No variables are in the tree');
        $tree->getFirstAndLastVariablesInTree();
    }

    /**
     * @return void
     */
    public function testCount(): void
    {
        self::assertEquals(3, count($this->tree));
    }

    /**
     * @return void
     */
    public function testCurrent(): void
    {
        $maxDepth = 0;

        foreach ($this->tree as $i => $node) {
            self::assertEquals($i, $this->tree->key());
            self::assertInstanceOf(CrosstabTreeNode::class, $node);

            $maxDepth = max($maxDepth, $this->tree->getDepth());
        }

        self::assertEquals(6, $maxDepth);

        self::assertNull($this->tree->key());

        $this->expectException(CrosstabOutOfBoundsException::class);
        $this->expectExceptionMessage('Iterator is invalid');
        $this->tree->current();
    }

    /**
     * @return void
     */
    public function testGetInnerIterator(): void
    {
        self::assertInstanceOf(CrosstabTreeIterator::class, $this->tree->getInnerIterator());
    }
}
