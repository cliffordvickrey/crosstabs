<?php

declare(strict_types=1);

namespace CliffordVickrey\Crosstabs\Tests\Unit\Tree;

use CliffordVickrey\Crosstabs\Tree\CrosstabTreeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(CrosstabTreeNode::class)]
class CrosstabTreeNodeTest extends TestCase
{
    /**
     * @var CrosstabTreeNode<stdClass>
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private CrosstabTreeNode $node;

    /**
     * @return void
     * @psalm-suppress InternalClass, MissingTemplateParam
     */
    public function setUp(): void
    {
        $parent = new stdClass();
        $parent->value = 'parent';

        $child = new stdClass();
        $child->value = 'child';

        /**
         * @extends CrosstabTreeNode<stdClass>
         */
        $anonChild = new class ($child) extends CrosstabTreeNode {
        };

        /**
         * @extends CrosstabTreeNode<stdClass>
         */
        $anonParent = new class ($parent, children: [$anonChild]) extends CrosstabTreeNode {
        };

        /** @var CrosstabTreeNode<stdClass> $anonParent */
        $this->node = $anonParent;
    }

    /**
     * @return void
     */
    public function testCount(): void
    {
        self::assertCount(1, $this->node);
    }

    /**
     * @return void
     */
    public function testClone(): void
    {
        $cloned = clone $this->node;

        $obj1 = $this->node->children[0]->payload;

        self::assertInstanceOf(stdClass::class, $obj1);

        $obj2 = $cloned->children[0]->payload;

        self::assertInstanceOf(stdClass::class, $obj2);

        $obj2->value = 'blah';
        self::assertNotEquals($obj1->value, $obj2->value);
    }
}
