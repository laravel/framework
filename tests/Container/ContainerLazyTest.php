<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Attributes\Lazy;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class ContainerLazyTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance();
    }

    public function testConcreteDoesNotThrowsExceptionWithAttribute()
    {
        $container = new Container;
        $lazy = $container->make(LazyWithAttributeStub::class);

        // No RuntimeException has occurred
        // LazyWithAttributeStub behaves like a Lazy Object, but this is not obvious from its type
        $this->assertInstanceOf(LazyWithAttributeStub::class, $lazy);
    }

    public function testConcreteThrowsExceptionWithoutAttribute()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Lazy call');

        $container = new Container;
        $container->make(LazyWithoutAttributeStub::class);
    }

    public function testConcreteDoesNotThrowsExceptionWithNoLogicConstructor()
    {
        $container = new Container;
        $lazy = $container->make(LazyWithAttributeStub::class);

        $this->assertInstanceOf(LazyWithAttributeStub::class, $lazy);

        $this->assertSame('work', $lazy->work());
    }

    public function testConcreteDoesThrowsExceptionWithConstructorWithLogic()
    {
        $container = new Container;
        $lazy = $container->make(LazyWithAttributeLogicStub::class);

        // No RuntimeException has occurred so far
        $this->assertInstanceOf(LazyWithAttributeLogicStub::class, $lazy);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Lazy call');

        // Only the call to number() causes a RuntimeException
        $lazy->number();
    }

    public function testConcreteThrowsExceptionButNotLazyDependency()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Parent call');

        $container = new Container;
        $container->make(LazyDependencyWithAttributeStub::class);
    }

    public function testConcreteNotLazyDependencyThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Lazy call');

        $container = new Container;
        $container->make(LazyDependencyWithoutAttributeStub::class);
    }
}

#[Lazy]
class LazyWithAttributeStub
{
    public function __construct()
    {
        throw new \RuntimeException('Lazy call');
    }

    public function work()
    {
        return 'work';
    }
}

class LazyWithTestRenameAttributeStub
{
    public function __construct()
    {
        throw new \RuntimeException('Lazy call');
    }

    public function work()
    {
        return 'work';
    }
}

#[Lazy]
class LazyWithAttributeLogicStub
{
    public $number;

    public function __construct()
    {
        $this->number = 10;

        throw new \RuntimeException('Lazy call');
    }

    public function number()
    {
        $this->number += 10;
    }
}

class LazyWithoutAttributeStub
{
    public function __construct()
    {
        throw new \RuntimeException('Lazy call');
    }
}

class LazyDependencyWithAttributeStub
{
    public function __construct(#[Lazy] LazyWithTestRenameAttributeStub $stub)
    {
        throw new \RuntimeException('Parent call');
    }
}

class LazyDependencyWithoutAttributeStub
{
    public function __construct(LazyWithoutAttributeStub $stub)
    {
        throw new \RuntimeException('Parent call');
    }
}
