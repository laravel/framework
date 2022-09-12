<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class ContainerResolveNonInstantiableTest extends TestCase
{
    public function testResolvingNonInstantiableWithDefaultRemovesWiths()
    {
        $container = new Container;
        $object = $container->make(ParentClass::class, ['i' => 42]);

        $this->assertSame(42, $object->i);
    }

    public function testResolvingNonInstantiableWithVariadicRemovesWiths()
    {
        $container = new Container;
        $parent = $container->make(VariadicParentClass::class, ['i' => 42]);

        $this->assertCount(0, $parent->child->objects);
        $this->assertSame(42, $parent->i);
    }

    public function testResolveVariadicPrimitive()
    {
        $container = new Container;
        $parent = $container->make(VariadicPrimitive::class);

        $this->assertSame($parent->params, []);
    }
}

interface TestInterface
{
}

class ParentClass
{
    /**
     * @var int
     */
    public $i;

    public function __construct(TestInterface $testObject = null, int $i = 0)
    {
        $this->i = $i;
    }
}

class VariadicParentClass
{
    /**
     * @var \Illuminate\Tests\Container\ChildClass
     */
    public $child;

    /**
     * @var int
     */
    public $i;

    public function __construct(ChildClass $child, int $i = 0)
    {
        $this->child = $child;
        $this->i = $i;
    }
}

class ChildClass
{
    /**
     * @var array
     */
    public $objects;

    public function __construct(TestInterface ...$objects)
    {
        $this->objects = $objects;
    }
}

class VariadicPrimitive
{
    /**
     * @var array
     */
    public $params;

    public function __construct(...$params)
    {
        $this->params = $params;
    }
}
