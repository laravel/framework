<?php

namespace Illuminate\Tests\Container;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;

class ContextualBindingTest extends TestCase
{
    public function testContainerCanInjectDifferentImplementationsDependingOnContext()
    {
        $container = new Container;

        $container->bind(IContainerContextContractStub::class, ContainerContextImplementationStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStub::class);
        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerContextImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $two->impl);

        /*
         * Test With Closures
         */
        $container = new Container;

        $container->bind(IContainerContextContractStub::class, ContainerContextImplementationStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStub::class);
        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContextContractStub::class)->give(function ($container) {
            return $container->make(ContainerContextImplementationStubTwo::class);
        });

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerContextImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $two->impl);
    }

    public function testContextualBindingWorksForExistingInstancedBindings()
    {
        $container = new Container;

        $container->instance(IContainerContextContractStub::class, new ContainerImplementationStub);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $container->make(ContainerTestContextInjectOne::class)->impl);
    }

    public function testContextualBindingWorksForNewlyInstancedBindings()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $container->instance(IContainerContextContractStub::class, new ContainerImplementationStub);

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnExistingAliasedInstances()
    {
        $container = new Container;

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', IContainerContextContractStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedInstances()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', IContainerContextContractStub::class);

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedBindings()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $container->bind('stub', ContainerContextImplementationStub::class);
        $container->alias('stub', IContainerContextContractStub::class);

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksForMultipleClasses()
    {
        $container = new Container;

        $container->bind(IContainerContextContractStub::class, ContainerContextImplementationStub::class);

        $container->when([ContainerTestContextInjectTwo::class, ContainerTestContextInjectThree::class])->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerContextImplementationStub::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectTwo::class)->impl
        );

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectThree::class)->impl
        );
    }

    public function testContextualBindingDoesntOverrideNonContextualResolution()
    {
        $container = new Container;

        $container->instance('stub', new ContainerContextImplementationStub);
        $container->alias('stub', IContainerContextContractStub::class);

        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectTwo::class)->impl
        );

        $this->assertInstanceOf(
            ContainerContextImplementationStub::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextuallyBoundInstancesAreNotUnnecessarilyRecreated()
    {
        ContainerTestContextInjectInstantiations::$instantiations = 0;

        $container = new Container;

        $container->instance(IContainerContextContractStub::class, new ContainerImplementationStub);
        $container->instance(ContainerTestContextInjectInstantiations::class, new ContainerTestContextInjectInstantiations);

        $this->assertEquals(1, ContainerTestContextInjectInstantiations::$instantiations);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerTestContextInjectInstantiations::class);

        $container->make(ContainerTestContextInjectOne::class);
        $container->make(ContainerTestContextInjectOne::class);
        $container->make(ContainerTestContextInjectOne::class);
        $container->make(ContainerTestContextInjectOne::class);

        $this->assertEquals(1, ContainerTestContextInjectInstantiations::$instantiations);
    }

    public function testContainerCanInjectSimpleVariable()
    {
        $container = new Container;
        $container->when(ContainerInjectVariableStub::class)->needs('$something')->give(100);
        $instance = $container->make(ContainerInjectVariableStub::class);
        $this->assertEquals(100, $instance->something);

        $container = new Container;
        $container->when(ContainerInjectVariableStub::class)->needs('$something')->give(function ($container) {
            return $container->make(ContainerConcreteStub::class);
        });
        $instance = $container->make(ContainerInjectVariableStub::class);
        $this->assertInstanceOf(ContainerConcreteStub::class, $instance->something);
    }

    public function testContextualBindingWorksWithAliasedTargets()
    {
        $container = new Container;

        $container->bind(IContainerContextContractStub::class, ContainerContextImplementationStub::class);
        $container->alias(IContainerContextContractStub::class, 'interface-stub');

        $container->alias(ContainerContextImplementationStub::class, 'stub-1');

        $container->when(ContainerTestContextInjectOne::class)->needs('interface-stub')->give('stub-1');
        $container->when(ContainerTestContextInjectTwo::class)->needs('interface-stub')->give(ContainerContextImplementationStubTwo::class);

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerContextImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $two->impl);
    }
}

interface IContainerContextContractStub
{
    //
}

class ContainerContextImplementationStub implements IContainerContextContractStub
{
    //
}

class ContainerContextImplementationStubTwo implements IContainerContextContractStub
{
    //
}

class ContainerTestContextInjectInstantiations implements IContainerContextContractStub
{
    public static $instantiations;

    public function __construct()
    {
        static::$instantiations++;
    }
}

class ContainerTestContextInjectOne
{
    public $impl;

    public function __construct(IContainerContextContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerTestContextInjectTwo
{
    public $impl;

    public function __construct(IContainerContextContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerTestContextInjectThree
{
    public $impl;

    public function __construct(IContainerContextContractStub $impl)
    {
        $this->impl = $impl;
    }
}
