<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Tree;

use CliffordVickrey\Crosstabs\Exception\CrosstabOutOfBoundException;
use CliffordVickrey\Crosstabs\Options\CrosstabCategory;
use CliffordVickrey\Crosstabs\Options\CrosstabVariable;
use CliffordVickrey\Crosstabs\Options\CrosstabVariableCollection;
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
        $vars = CrosstabVariableCollection::__set_state(array(
            'variables' =>
                array(
                    0 =>
                        CrosstabVariable::__set_state(array(
                            'description' => 'Device Type',
                            'name' => 'Device Type',
                            'categories' =>
                                array(
                                    0 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'Desktop',
                                            'name' => 'Desktop',
                                        )),
                                    1 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'Mobile Device',
                                            'name' => 'Mobile Device',
                                        )),
                                    2 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'Tablet',
                                            'name' => 'Tablet',
                                        )),
                                ),
                        )),
                    1 =>
                        CrosstabVariable::__set_state(array(
                            'description' => 'Platform',
                            'name' => 'Platform',
                            'categories' =>
                                array(
                                    0 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'iOS',
                                            'name' => 'iOS',
                                        )),
                                    1 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'Linux',
                                            'name' => 'Linux',
                                        )),
                                    2 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'MacOSX',
                                            'name' => 'MacOSX',
                                        )),
                                    3 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'Windows',
                                            'name' => 'Windows',
                                        )),
                                ),
                        )),
                    2 =>
                        CrosstabVariable::__set_state(array(
                            'description' => 'Browser',
                            'name' => 'Browser',
                            'categories' =>
                                array(
                                    0 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'Chrome',
                                            'name' => 'Chrome',
                                        )),
                                    1 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'Edge',
                                            'name' => 'Edge',
                                        )),
                                    2 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'Firefox',
                                            'name' => 'Firefox',
                                        )),
                                    3 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'IE',
                                            'name' => 'IE',
                                        )),
                                    4 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'Netscape',
                                            'name' => 'Netscape',
                                        )),
                                    5 =>
                                        CrosstabCategory::__set_state(array(
                                            'description' => 'Safari',
                                            'name' => 'Safari',
                                        )),
                                ),
                        )),
                ),
        ));

        $this->tree = new CrosstabTree($vars);
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

        $this->expectException(CrosstabOutOfBoundException::class);
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

        $this->expectException(CrosstabOutOfBoundException::class);
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
