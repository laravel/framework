<?php

namespace Illuminate\Tests\Container;

use stdClass;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;

class ContextualBindingTest extends TestCase
{
    public function testContainerCanInjectDifferentImplementationsDependingOnContext()
    {
        $container = new Container;

        $container->bind(IContainerContractStub::class, ContainerImplementationStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContractStub::class)->give(ContainerImplementationStub::class);
        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContractStub::class)->give(ContainerImplementationStubTwo::class);

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerImplementationStubTwo::class, $two->impl);

        /*
         * Test With Closures
         */
        $container = new Container;

        $container->bind(IContainerContractStub::class, ContainerImplementationStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContractStub::class)->give(ContainerImplementationStub::class);
        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContractStub::class)->give(function ($container) {
            return $container->make(ContainerImplementationStubTwo::class);
        });

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerImplementationStubTwo::class, $two->impl);
    }

    public function testContextualBindingWorksForExistingInstancedBindings()
    {
        $container = new Container;

        $container->instance(IContainerContractStub::class, new ContainerImplementationStub);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContractStub::class)->give(ContainerImplementationStubTwo::class);

        $this->assertInstanceOf(ContainerImplementationStubTwo::class, $container->make(ContainerTestContextInjectOne::class)->impl);
    }

    public function testContextualBindingWorksForNewlyInstancedBindings()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContractStub::class)->give(ContainerImplementationStubTwo::class);

        $container->instance(IContainerContractStub::class, new ContainerImplementationStub);

        $this->assertInstanceOf(
            ContainerImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnExistingAliasedInstances()
    {
        $container = new Container;

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', IContainerContractStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContractStub::class)->give(ContainerImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedInstances()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContractStub::class)->give(ContainerImplementationStubTwo::class);

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', IContainerContractStub::class);

        $this->assertInstanceOf(
            ContainerImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedBindings()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContractStub::class)->give(ContainerImplementationStubTwo::class);

        $container->bind('stub', ContainerImplementationStub::class);
        $container->alias('stub', IContainerContractStub::class);

        $this->assertInstanceOf(
            ContainerImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksForMultipleClasses()
    {
        $container = new Container;

        $container->bind(IContainerContractStub::class, ContainerImplementationStub::class);

        $container->when([ContainerTestContextInjectTwo::class, ContainerTestContextInjectThree::class])->needs(IContainerContractStub::class)->give(ContainerImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerImplementationStub::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );

        $this->assertInstanceOf(
            ContainerImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectTwo::class)->impl
        );

        $this->assertInstanceOf(
            ContainerImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectThree::class)->impl
        );
    }

    public function testContextualBindingDoesntOverrideNonContextualResolution()
    {
        $container = new Container;

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', IContainerContractStub::class);

        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContractStub::class)->give(ContainerImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectTwo::class)->impl
        );

        $this->assertInstanceOf(
            ContainerImplementationStub::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextuallyBoundInstancesAreNotUnnecessarilyRecreated()
    {
        ContainerTestContextInjectInstantiations::$instantiations = 0;

        $container = new Container;

        $container->instance(IContainerContractStub::class, new ContainerImplementationStub);
        $container->instance(ContainerTestContextInjectInstantiations::class, new ContainerTestContextInjectInstantiations);

        $this->assertEquals(1, ContainerTestContextInjectInstantiations::$instantiations);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContractStub::class)->give(ContainerTestContextInjectInstantiations::class);

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

        $container->bind(IContainerContractStub::class, ContainerImplementationStub::class);
        $container->alias(IContainerContractStub::class, 'interface-stub');

        $container->alias(ContainerImplementationStub::class, 'stub-1');

        $container->when(ContainerTestContextInjectOne::class)->needs('interface-stub')->give('stub-1');
        $container->when(ContainerTestContextInjectTwo::class)->needs('interface-stub')->give(ContainerImplementationStubTwo::class);

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerImplementationStubTwo::class, $two->impl);
    }
}

class ContainerTestContextInjectInstantiations implements IContainerContractStub
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

    public function __construct(IContainerContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerTestContextInjectTwo
{
    public $impl;

    public function __construct(IContainerContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerTestContextInjectThree
{
    public $impl;

    public function __construct(IContainerContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerConstructorParameterLoggingStub
{
    public $receivedParameters;

    public function __construct($first, $second)
    {
        $this->receivedParameters = func_get_args();
    }
}
