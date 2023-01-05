<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Container;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use stdClass;
use TypeError;

class ContainerTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function testContainerSingleton()
    {
        $container = Container::setInstance(new Container);

        $this->assertSame($container, Container::getInstance());

        Container::setInstance(null);

        $container2 = Container::getInstance();

        $this->assertInstanceOf(Container::class, $container2);
        $this->assertNotSame($container, $container2);
    }

    public function testClosureResolution()
    {
        $container = new Container;
        $container->bind('name', function () {
            return 'Taylor';
        });
        $this->assertSame('Taylor', $container->make('name'));
    }

    public function testBindIfDoesntRegisterIfServiceAlreadyRegistered()
    {
        $container = new Container;
        $container->bind('name', function () {
            return 'Taylor';
        });
        $container->bindIf('name', function () {
            return 'Dayle';
        });

        $this->assertSame('Taylor', $container->make('name'));
    }

    public function testBindIfDoesRegisterIfServiceNotRegisteredYet()
    {
        $container = new Container;
        $container->bind('surname', function () {
            return 'Taylor';
        });
        $container->bindIf('name', function () {
            return 'Dayle';
        });

        $this->assertSame('Dayle', $container->make('name'));
    }

    public function testSingletonIfDoesntRegisterIfBindingAlreadyRegistered()
    {
        $container = new Container;
        $container->singleton('class', function () {
            return new stdClass;
        });
        $firstInstantiation = $container->make('class');
        $container->singletonIf('class', function () {
            return new ContainerConcreteStub;
        });
        $secondInstantiation = $container->make('class');
        $this->assertSame($firstInstantiation, $secondInstantiation);
    }

    public function testSingletonIfDoesRegisterIfBindingNotRegisteredYet()
    {
        $container = new Container;
        $container->singleton('class', function () {
            return new stdClass;
        });
        $container->singletonIf('otherClass', function () {
            return new ContainerConcreteStub;
        });
        $firstInstantiation = $container->make('otherClass');
        $secondInstantiation = $container->make('otherClass');
        $this->assertSame($firstInstantiation, $secondInstantiation);
    }

    public function testSharedClosureResolution()
    {
        $container = new Container;
        $container->singleton('class', function () {
            return new stdClass;
        });
        $firstInstantiation = $container->make('class');
        $secondInstantiation = $container->make('class');
        $this->assertSame($firstInstantiation, $secondInstantiation);
    }

    public function testScopedClosureResolution()
    {
        $container = new Container;
        $container->scoped('class', function () {
            return new stdClass;
        });
        $firstInstantiation = $container->make('class');
        $secondInstantiation = $container->make('class');
        $this->assertSame($firstInstantiation, $secondInstantiation);
    }

    public function testScopedIf()
    {
        $container = new Container;
        $container->scopedIf('class', function () {
            return 'foo';
        });
        $this->assertSame('foo', $container->make('class'));
        $container->scopedIf('class', function () {
            return 'bar';
        });
        $this->assertSame('foo', $container->make('class'));
        $this->assertNotSame('bar', $container->make('class'));
    }

    public function testScopedClosureResets()
    {
        $container = new Container;
        $container->scoped('class', function () {
            return new stdClass;
        });
        $firstInstantiation = $container->make('class');

        $container->forgetScopedInstances();

        $secondInstantiation = $container->make('class');
        $this->assertNotSame($firstInstantiation, $secondInstantiation);
    }

    public function testAutoConcreteResolution()
    {
        $container = new Container;
        $this->assertInstanceOf(ContainerConcreteStub::class, $container->make(ContainerConcreteStub::class));
    }

    public function testSharedConcreteResolution()
    {
        $container = new Container;
        $container->singleton(ContainerConcreteStub::class);

        $var1 = $container->make(ContainerConcreteStub::class);
        $var2 = $container->make(ContainerConcreteStub::class);
        $this->assertSame($var1, $var2);
    }

    public function testScopedConcreteResolutionResets()
    {
        $container = new Container;
        $container->scoped(ContainerConcreteStub::class);

        $var1 = $container->make(ContainerConcreteStub::class);

        $container->forgetScopedInstances();

        $var2 = $container->make(ContainerConcreteStub::class);

        $this->assertNotSame($var1, $var2);
    }

    public function testBindFailsLoudlyWithInvalidArgument()
    {
        $this->expectException(TypeError::class);
        $container = new Container;

        $concrete = new ContainerConcreteStub;
        $container->bind(ContainerConcreteStub::class, $concrete);
    }

    public function testAbstractToConcreteResolution()
    {
        $container = new Container;
        $container->bind(IContainerContractStub::class, ContainerImplementationStub::class);
        $class = $container->make(ContainerDependentStub::class);
        $this->assertInstanceOf(ContainerImplementationStub::class, $class->impl);
    }

    public function testNestedDependencyResolution()
    {
        $container = new Container;
        $container->bind(IContainerContractStub::class, ContainerImplementationStub::class);
        $class = $container->make(ContainerNestedDependentStub::class);
        $this->assertInstanceOf(ContainerDependentStub::class, $class->inner);
        $this->assertInstanceOf(ContainerImplementationStub::class, $class->inner->impl);
    }

    public function testContainerIsPassedToResolvers()
    {
        $container = new Container;
        $container->bind('something', function ($c) {
            return $c;
        });
        $c = $container->make('something');
        $this->assertSame($c, $container);
    }

    public function testArrayAccess()
    {
        $container = new Container;
        $this->assertFalse(isset($container['something']));
        $container['something'] = function () {
            return 'foo';
        };
        $this->assertTrue(isset($container['something']));
        $this->assertNotEmpty($container['something']);
        $this->assertSame('foo', $container['something']);
        unset($container['something']);
        $this->assertFalse(isset($container['something']));

        //test offsetSet when it's not instanceof Closure
        $container = new Container;
        $container['something'] = 'text';
        $this->assertTrue(isset($container['something']));
        $this->assertNotEmpty($container['something']);
        $this->assertSame('text', $container['something']);
        unset($container['something']);
        $this->assertFalse(isset($container['something']));
    }

    public function testAliases()
    {
        $container = new Container;
        $container['foo'] = 'bar';
        $container->alias('foo', 'baz');
        $container->alias('baz', 'bat');
        $this->assertSame('bar', $container->make('foo'));
        $this->assertSame('bar', $container->make('baz'));
        $this->assertSame('bar', $container->make('bat'));
    }

    public function testAliasesWithArrayOfParameters()
    {
        $container = new Container;
        $container->bind('foo', function ($app, $config) {
            return $config;
        });
        $container->alias('foo', 'baz');
        $this->assertEquals([1, 2, 3], $container->make('baz', [1, 2, 3]));
    }

    public function testBindingsCanBeOverridden()
    {
        $container = new Container;
        $container['foo'] = 'bar';
        $container['foo'] = 'baz';
        $this->assertSame('baz', $container['foo']);
    }

    public function testBindingAnInstanceReturnsTheInstance()
    {
        $container = new Container;

        $bound = new stdClass;
        $resolved = $container->instance('foo', $bound);

        $this->assertSame($bound, $resolved);
    }

    public function testBindingAnInstanceAsShared()
    {
        $container = new Container;
        $bound = new stdClass;
        $container->instance('foo', $bound);
        $object = $container->make('foo');
        $this->assertSame($bound, $object);
    }

    public function testResolutionOfDefaultParameters()
    {
        $container = new Container;
        $instance = $container->make(ContainerDefaultValueStub::class);
        $this->assertInstanceOf(ContainerConcreteStub::class, $instance->stub);
        $this->assertSame('taylor', $instance->default);
    }

    public function testBound()
    {
        $container = new Container;
        $container->bind(ContainerConcreteStub::class, function () {
            //
        });
        $this->assertTrue($container->bound(ContainerConcreteStub::class));
        $this->assertFalse($container->bound(IContainerContractStub::class));

        $container = new Container;
        $container->bind(IContainerContractStub::class, ContainerConcreteStub::class);
        $this->assertTrue($container->bound(IContainerContractStub::class));
        $this->assertFalse($container->bound(ContainerConcreteStub::class));
    }

    public function testUnsetRemoveBoundInstances()
    {
        $container = new Container;
        $container->instance('object', new stdClass);
        unset($container['object']);

        $this->assertFalse($container->bound('object'));
    }

    public function testBoundInstanceAndAliasCheckViaArrayAccess()
    {
        $container = new Container;
        $container->instance('object', new stdClass);
        $container->alias('object', 'alias');

        $this->assertTrue(isset($container['object']));
        $this->assertTrue(isset($container['alias']));
    }

    public function testReboundListeners()
    {
        unset($_SERVER['__test.rebind']);

        $container = new Container;
        $container->bind('foo', function () {
            //
        });
        $container->rebinding('foo', function () {
            $_SERVER['__test.rebind'] = true;
        });
        $container->bind('foo', function () {
            //
        });

        $this->assertTrue($_SERVER['__test.rebind']);
    }

    public function testReboundListenersOnInstances()
    {
        unset($_SERVER['__test.rebind']);

        $container = new Container;
        $container->instance('foo', function () {
            //
        });
        $container->rebinding('foo', function () {
            $_SERVER['__test.rebind'] = true;
        });
        $container->instance('foo', function () {
            //
        });

        $this->assertTrue($_SERVER['__test.rebind']);
    }

    public function testReboundListenersOnInstancesOnlyFiresIfWasAlreadyBound()
    {
        $_SERVER['__test.rebind'] = false;

        $container = new Container;
        $container->rebinding('foo', function () {
            $_SERVER['__test.rebind'] = true;
        });
        $container->instance('foo', function () {
            //
        });

        $this->assertFalse($_SERVER['__test.rebind']);
    }

    public function testInternalClassWithDefaultParameters()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Unresolvable dependency resolving [Parameter #0 [ <required> $first ]] in class Illuminate\Tests\Container\ContainerMixedPrimitiveStub');

        $container = new Container;
        $container->make(ContainerMixedPrimitiveStub::class, []);
    }

    public function testBindingResolutionExceptionMessage()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Target [Illuminate\Tests\Container\IContainerContractStub] is not instantiable.');

        $container = new Container;
        $container->make(IContainerContractStub::class, []);
    }

    public function testBindingResolutionExceptionMessageIncludesBuildStack()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Target [Illuminate\Tests\Container\IContainerContractStub] is not instantiable while building [Illuminate\Tests\Container\ContainerDependentStub].');

        $container = new Container;
        $container->make(ContainerDependentStub::class, []);
    }

    public function testBindingResolutionExceptionMessageWhenClassDoesNotExist()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Target class [Foo\Bar\Baz\DummyClass] does not exist.');

        $container = new Container;
        $container->build('Foo\Bar\Baz\DummyClass');
    }

    public function testForgetInstanceForgetsInstance()
    {
        $container = new Container;
        $containerConcreteStub = new ContainerConcreteStub;
        $container->instance(ContainerConcreteStub::class, $containerConcreteStub);
        $this->assertTrue($container->isShared(ContainerConcreteStub::class));
        $container->forgetInstance(ContainerConcreteStub::class);
        $this->assertFalse($container->isShared(ContainerConcreteStub::class));
    }

    public function testForgetInstancesForgetsAllInstances()
    {
        $container = new Container;
        $containerConcreteStub1 = new ContainerConcreteStub;
        $containerConcreteStub2 = new ContainerConcreteStub;
        $containerConcreteStub3 = new ContainerConcreteStub;
        $container->instance('Instance1', $containerConcreteStub1);
        $container->instance('Instance2', $containerConcreteStub2);
        $container->instance('Instance3', $containerConcreteStub3);
        $this->assertTrue($container->isShared('Instance1'));
        $this->assertTrue($container->isShared('Instance2'));
        $this->assertTrue($container->isShared('Instance3'));
        $container->forgetInstances();
        $this->assertFalse($container->isShared('Instance1'));
        $this->assertFalse($container->isShared('Instance2'));
        $this->assertFalse($container->isShared('Instance3'));
    }

    public function testContainerFlushFlushesAllBindingsAliasesAndResolvedInstances()
    {
        $container = new Container;
        $container->bind('ConcreteStub', function () {
            return new ContainerConcreteStub;
        }, true);
        $container->alias('ConcreteStub', 'ContainerConcreteStub');
        $container->make('ConcreteStub');
        $this->assertTrue($container->resolved('ConcreteStub'));
        $this->assertTrue($container->isAlias('ContainerConcreteStub'));
        $this->assertArrayHasKey('ConcreteStub', $container->getBindings());
        $this->assertTrue($container->isShared('ConcreteStub'));
        $container->flush();
        $this->assertFalse($container->resolved('ConcreteStub'));
        $this->assertFalse($container->isAlias('ContainerConcreteStub'));
        $this->assertEmpty($container->getBindings());
        $this->assertFalse($container->isShared('ConcreteStub'));
    }

    public function testResolvedResolvesAliasToBindingNameBeforeChecking()
    {
        $container = new Container;
        $container->bind('ConcreteStub', function () {
            return new ContainerConcreteStub;
        }, true);
        $container->alias('ConcreteStub', 'foo');

        $this->assertFalse($container->resolved('ConcreteStub'));
        $this->assertFalse($container->resolved('foo'));

        $container->make('ConcreteStub');

        $this->assertTrue($container->resolved('ConcreteStub'));
        $this->assertTrue($container->resolved('foo'));
    }

    public function testGetAlias()
    {
        $container = new Container;
        $container->alias('ConcreteStub', 'foo');
        $this->assertSame('ConcreteStub', $container->getAlias('foo'));
    }

    public function testGetAliasRecursive()
    {
        $container = new Container;
        $container->alias('ConcreteStub', 'foo');
        $container->alias('foo', 'bar');
        $container->alias('bar', 'baz');
        $this->assertSame('ConcreteStub', $container->getAlias('baz'));
        $this->assertTrue($container->isAlias('baz'));
        $this->assertTrue($container->isAlias('bar'));
        $this->assertTrue($container->isAlias('foo'));
    }

    public function testItThrowsExceptionWhenAbstractIsSameAsAlias()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('[name] is aliased to itself.');

        $container = new Container;
        $container->alias('name', 'name');
    }

    public function testContainerGetFactory()
    {
        $container = new Container;
        $container->bind('name', function () {
            return 'Taylor';
        });

        $factory = $container->factory('name');
        $this->assertEquals($container->make('name'), $factory());
    }

    public function testMakeWithMethodIsAnAliasForMakeMethod()
    {
        $mock = $this->getMockBuilder(Container::class)
                     ->onlyMethods(['make'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('make')
             ->with(ContainerDefaultValueStub::class, ['default' => 'laurence'])
             ->willReturn(new stdClass);

        $result = $mock->makeWith(ContainerDefaultValueStub::class, ['default' => 'laurence']);

        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testResolvingWithArrayOfParameters()
    {
        $container = new Container;
        $instance = $container->make(ContainerDefaultValueStub::class, ['default' => 'adam']);
        $this->assertSame('adam', $instance->default);

        $instance = $container->make(ContainerDefaultValueStub::class);
        $this->assertSame('taylor', $instance->default);

        $container->bind('foo', function ($app, $config) {
            return $config;
        });

        $this->assertEquals([1, 2, 3], $container->make('foo', [1, 2, 3]));
    }

    public function testResolvingWithArrayOfMixedParameters()
    {
        $container = new Container;
        $instance = $container->make(ContainerMixedPrimitiveStub::class, ['first' => 1, 'last' => 2, 'third' => 3]);
        $this->assertSame(1, $instance->first);
        $this->assertInstanceOf(ContainerConcreteStub::class, $instance->stub);
        $this->assertSame(2, $instance->last);
        $this->assertFalse(isset($instance->third));
    }

    public function testResolvingWithUsingAnInterface()
    {
        $container = new Container;
        $container->bind(IContainerContractStub::class, ContainerInjectVariableStubWithInterfaceImplementation::class);
        $instance = $container->make(IContainerContractStub::class, ['something' => 'laurence']);
        $this->assertSame('laurence', $instance->something);
    }

    public function testNestedParameterOverride()
    {
        $container = new Container;
        $container->bind('foo', function ($app, $config) {
            return $app->make('bar', ['name' => 'Taylor']);
        });
        $container->bind('bar', function ($app, $config) {
            return $config;
        });

        $this->assertEquals(['name' => 'Taylor'], $container->make('foo', ['something']));
    }

    public function testNestedParametersAreResetForFreshMake()
    {
        $container = new Container;

        $container->bind('foo', function ($app, $config) {
            return $app->make('bar');
        });

        $container->bind('bar', function ($app, $config) {
            return $config;
        });

        $this->assertEquals([], $container->make('foo', ['something']));
    }

    public function testSingletonBindingsNotRespectedWithMakeParameters()
    {
        $container = new Container;

        $container->singleton('foo', function ($app, $config) {
            return $config;
        });

        $this->assertEquals(['name' => 'taylor'], $container->make('foo', ['name' => 'taylor']));
        $this->assertEquals(['name' => 'abigail'], $container->make('foo', ['name' => 'abigail']));
    }

    public function testCanBuildWithoutParameterStackWithNoConstructors()
    {
        $container = new Container;
        $this->assertInstanceOf(ContainerConcreteStub::class, $container->build(ContainerConcreteStub::class));
    }

    public function testCanBuildWithoutParameterStackWithConstructors()
    {
        $container = new Container;
        $container->bind(IContainerContractStub::class, ContainerImplementationStub::class);
        $this->assertInstanceOf(ContainerDependentStub::class, $container->build(ContainerDependentStub::class));
    }

    public function testContainerKnowsEntry()
    {
        $container = new Container;
        $container->bind(IContainerContractStub::class, ContainerImplementationStub::class);
        $this->assertTrue($container->has(IContainerContractStub::class));
    }

    public function testContainerCanBindAnyWord()
    {
        $container = new Container;
        $container->bind('Taylor', stdClass::class);
        $this->assertInstanceOf(stdClass::class, $container->get('Taylor'));
    }

    public function testContainerCanDynamicallySetService()
    {
        $container = new Container;
        $this->assertFalse(isset($container['name']));
        $container['name'] = 'Taylor';
        $this->assertTrue(isset($container['name']));
        $this->assertSame('Taylor', $container['name']);
    }

    public function testUnknownEntryThrowsException()
    {
        $this->expectException(EntryNotFoundException::class);

        $container = new Container;
        $container->get('Taylor');
    }

    public function testBoundEntriesThrowsContainerExceptionWhenNotResolvable()
    {
        $this->expectException(ContainerExceptionInterface::class);

        $container = new Container;
        $container->bind('Taylor', IContainerContractStub::class);

        $container->get('Taylor');
    }

    public function testContainerCanResolveClasses()
    {
        $container = new Container;
        $class = $container->get(ContainerConcreteStub::class);

        $this->assertInstanceOf(ContainerConcreteStub::class, $class);
    }

    public function testMethodLevelContextualBinding()
    {
        $container = new Container;

        $container->bind(IContainerContractStub::class, ContainerImplementationStubTwo::class);

        $container->when(ContainerContextualBindingCallTarget::class)
                ->needs(IContainerContractStub::class)
                ->give(ContainerImplementationStub::class);

        $result = $container->call([new ContainerContextualBindingCallTarget, 'work']);

        $this->assertInstanceOf(ContainerImplementationStub::class, $result);
    }

    // public function testContainerCanCatchCircularDependency()
    // {
    //     $this->expectException(\Illuminate\Contracts\Container\CircularDependencyException::class);

    //     $container = new Container;
    //     $container->get(CircularAStub::class);
    // }
}

class CircularAStub
{
    public function __construct(CircularBStub $b)
    {
        //
    }
}

class CircularBStub
{
    public function __construct(CircularCStub $c)
    {
        //
    }
}

class CircularCStub
{
    public function __construct(CircularAStub $a)
    {
        //
    }
}

class ContainerConcreteStub
{
    //
}

interface IContainerContractStub
{
    //
}

class ContainerImplementationStub implements IContainerContractStub
{
    //
}

class ContainerImplementationStubTwo implements IContainerContractStub
{
    //
}

class ContainerDependentStub
{
    public $impl;

    public function __construct(IContainerContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerNestedDependentStub
{
    public $inner;

    public function __construct(ContainerDependentStub $inner)
    {
        $this->inner = $inner;
    }
}

class ContainerDefaultValueStub
{
    public $stub;
    public $default;

    public function __construct(ContainerConcreteStub $stub, $default = 'taylor')
    {
        $this->stub = $stub;
        $this->default = $default;
    }
}

class ContainerMixedPrimitiveStub
{
    public $first;
    public $last;
    public $stub;

    public function __construct($first, ContainerConcreteStub $stub, $last)
    {
        $this->stub = $stub;
        $this->last = $last;
        $this->first = $first;
    }
}

class ContainerInjectVariableStub
{
    public $something;

    public function __construct(ContainerConcreteStub $concrete, $something)
    {
        $this->something = $something;
    }
}

class ContainerInjectVariableStubWithInterfaceImplementation implements IContainerContractStub
{
    public $something;

    public function __construct(ContainerConcreteStub $concrete, $something)
    {
        $this->something = $something;
    }
}

class ContainerContextualBindingCallTarget
{
    public function __construct()
    {
    }

    public function work(IContainerContractStub $stub)
    {
        return $stub;
    }
}
