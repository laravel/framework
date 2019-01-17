<?php

namespace Illuminate\Tests\Container;

use stdClass;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;

class ContainerTest extends TestCase
{
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
        $this->assertEquals('Taylor', $container->make('name'));
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

        $this->assertEquals('Taylor', $container->make('name'));
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

        $this->assertEquals('Dayle', $container->make('name'));
    }

    public function testSharedClosureResolution()
    {
        $container = new Container;
        $class = new stdClass;
        $container->singleton('class', function () use ($class) {
            return $class;
        });
        $this->assertSame($class, $container->make('class'));
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
        $container['something'] = function () {
            return 'foo';
        };
        $this->assertTrue(isset($container['something']));
        $this->assertEquals('foo', $container['something']);
        unset($container['something']);
        $this->assertFalse(isset($container['something']));
    }

    public function testAliases()
    {
        $container = new Container;
        $container['foo'] = 'bar';
        $container->alias('foo', 'baz');
        $container->alias('baz', 'bat');
        $this->assertEquals('bar', $container->make('foo'));
        $this->assertEquals('bar', $container->make('baz'));
        $this->assertEquals('bar', $container->make('bat'));
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
        $this->assertEquals('baz', $container['foo']);
    }

    public function testBindingAnInstanceReturnsTheInstance()
    {
        $container = new Container;

        $bound = new stdClass;
        $resolved = $container->instance('foo', $bound);

        $this->assertSame($bound, $resolved);
    }

    public function testResolutionOfDefaultParameters()
    {
        $container = new Container;
        $instance = $container->make(ContainerDefaultValueStub::class);
        $this->assertInstanceOf(ContainerConcreteStub::class, $instance->stub);
        $this->assertEquals('taylor', $instance->default);
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

    /**
     * @expectedException \Illuminate\Contracts\Container\BindingResolutionException
     * @expectedExceptionMessage Unresolvable dependency resolving [Parameter #0 [ <required> $first ]] in class Illuminate\Tests\Container\ContainerMixedPrimitiveStub
     */
    public function testInternalClassWithDefaultParameters()
    {
        $container = new Container;
        $container->make(ContainerMixedPrimitiveStub::class, []);
    }

    /**
     * @expectedException \Illuminate\Contracts\Container\BindingResolutionException
     * @expectedExceptionMessage Target [Illuminate\Tests\Container\IContainerContractStub] is not instantiable.
     */
    public function testBindingResolutionExceptionMessage()
    {
        $container = new Container;
        $container->make(IContainerContractStub::class, []);
    }

    /**
     * @expectedException \Illuminate\Contracts\Container\BindingResolutionException
     * @expectedExceptionMessage Target [Illuminate\Tests\Container\IContainerContractStub] is not instantiable while building [Illuminate\Tests\Container\ContainerDependentStub].
     */
    public function testBindingResolutionExceptionMessageIncludesBuildStack()
    {
        $container = new Container;
        $container->make(ContainerDependentStub::class, []);
    }

    public function testContainerTags()
    {
        $container = new Container;
        $container->tag(ContainerImplementationStub::class, 'foo', 'bar');
        $container->tag(ContainerImplementationStubTwo::class, ['foo']);

        $this->assertCount(1, $container->tagged('bar'));
        $this->assertCount(2, $container->tagged('foo'));

        $fooResults = [];
        foreach ($container->tagged('foo') as $foo) {
            $fooResults[] = $foo;
        }

        $barResults = [];
        foreach ($container->tagged('bar') as $bar) {
            $barResults[] = $bar;
        }

        $this->assertInstanceOf(ContainerImplementationStub::class, $fooResults[0]);
        $this->assertInstanceOf(ContainerImplementationStub::class, $barResults[0]);
        $this->assertInstanceOf(ContainerImplementationStubTwo::class, $fooResults[1]);

        $container = new Container;
        $container->tag([ContainerImplementationStub::class, ContainerImplementationStubTwo::class], ['foo']);
        $this->assertCount(2, $container->tagged('foo'));

        $fooResults = [];
        foreach ($container->tagged('foo') as $foo) {
            $fooResults[] = $foo;
        }

        $this->assertInstanceOf(ContainerImplementationStub::class, $fooResults[0]);
        $this->assertInstanceOf(ContainerImplementationStubTwo::class, $fooResults[1]);

        $this->assertCount(0, $container->tagged('this_tag_does_not_exist'));
    }

    public function testTaggedServicesAreLazyLoaded()
    {
        $container = $this->createPartialMock(Container::class, ['make']);
        $container->expects($this->once())->method('make')->willReturn(new ContainerImplementationStub());

        $container->tag(ContainerImplementationStub::class, ['foo']);
        $container->tag(ContainerImplementationStubTwo::class, ['foo']);

        $fooResults = [];
        foreach ($container->tagged('foo') as $foo) {
            $fooResults[] = $foo;
            break;
        }

        $this->assertCount(2, $container->tagged('foo'));
        $this->assertInstanceOf(ContainerImplementationStub::class, $fooResults[0]);
    }

    public function testLazyLoadedTaggedServicesCanBeLoopedOverMultipleTimes()
    {
        $container = new Container;
        $container->tag(ContainerImplementationStub::class, 'foo');
        $container->tag(ContainerImplementationStubTwo::class, ['foo']);

        $services = $container->tagged('foo');

        $fooResults = [];
        foreach ($services as $foo) {
            $fooResults[] = $foo;
        }

        $this->assertInstanceOf(ContainerImplementationStub::class, $fooResults[0]);
        $this->assertInstanceOf(ContainerImplementationStubTwo::class, $fooResults[1]);

        $fooResults = [];
        foreach ($services as $foo) {
            $fooResults[] = $foo;
        }

        $this->assertInstanceOf(ContainerImplementationStub::class, $fooResults[0]);
        $this->assertInstanceOf(ContainerImplementationStubTwo::class, $fooResults[1]);
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
        $this->assertEquals($container->getAlias('foo'), 'ConcreteStub');
    }

    public function testItThrowsExceptionWhenAbstractIsSameAsAlias()
    {
        $container = new Container;
        $container->alias('name', 'name');

        $this->expectException('LogicException');
        $this->expectExceptionMessage('[name] is aliased to itself.');

        $container->getAlias('name');
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
                     ->setMethods(['make'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('make')
             ->with(ContainerDefaultValueStub::class, ['default' => 'laurence'])
             ->will($this->returnValue(new stdClass));

        $result = $mock->makeWith(ContainerDefaultValueStub::class, ['default' => 'laurence']);

        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testResolvingWithArrayOfParameters()
    {
        $container = new Container;
        $instance = $container->make(ContainerDefaultValueStub::class, ['default' => 'adam']);
        $this->assertEquals('adam', $instance->default);

        $instance = $container->make(ContainerDefaultValueStub::class);
        $this->assertEquals('taylor', $instance->default);

        $container->bind('foo', function ($app, $config) {
            return $config;
        });

        $this->assertEquals([1, 2, 3], $container->make('foo', [1, 2, 3]));
    }

    public function testResolvingWithUsingAnInterface()
    {
        $container = new Container;
        $container->bind(IContainerContractStub::class, ContainerInjectVariableStubWithInterfaceImplementation::class);
        $instance = $container->make(IContainerContractStub::class, ['something' => 'laurence']);
        $this->assertEquals('laurence', $instance->something);
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

    /**
     * @expectedException \Illuminate\Container\EntryNotFoundException
     */
    public function testUnknownEntryThrowsException()
    {
        $container = new Container;
        $container->get('Taylor');
    }

    /**
     * @expectedException \Psr\Container\ContainerExceptionInterface
     */
    public function testBoundEntriesThrowsContainerExceptionWhenNotResolvable()
    {
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
