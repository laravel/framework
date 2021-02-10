<?php

namespace Illuminate\Tests\Container;

use Error;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class ContainerResolveNonInstantiableTest extends TestCase
{
    public function testResolvingNonInstantiableRemovesWiths()
    {
        $container = new Container;
        $object = $container->make(ParentClass::class, ['i' => 42]);

        $this->assertSame(42, $object->i);
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
