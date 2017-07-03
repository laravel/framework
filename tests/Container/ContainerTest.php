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
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $container->make('Illuminate\Tests\Container\ContainerConcreteStub'));
    }

    public function testSharedConcreteResolution()
    {
        $container = new Container;
        $container->singleton('Illuminate\Tests\Container\ContainerConcreteStub');

        $var1 = $container->make('Illuminate\Tests\Container\ContainerConcreteStub');
        $var2 = $container->make('Illuminate\Tests\Container\ContainerConcreteStub');
        $this->assertSame($var1, $var2);
    }

    public function testAbstractToConcreteResolution()
    {
        $container = new Container;
        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
        $class = $container->make('Illuminate\Tests\Container\ContainerDependentStub');
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $class->impl);
    }

    public function testNestedDependencyResolution()
    {
        $container = new Container;
        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
        $class = $container->make('Illuminate\Tests\Container\ContainerNestedDependentStub');
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerDependentStub', $class->inner);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $class->inner->impl);
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

    public function testExtendedBindings()
    {
        $container = new Container;
        $container['foo'] = 'foo';
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });

        $this->assertEquals('foobar', $container->make('foo'));

        $container = new Container;

        $container->singleton('foo', function () {
            return (object) ['name' => 'taylor'];
        });
        $container->extend('foo', function ($old, $container) {
            $old->age = 26;

            return $old;
        });

        $result = $container->make('foo');

        $this->assertEquals('taylor', $result->name);
        $this->assertEquals(26, $result->age);
        $this->assertSame($result, $container->make('foo'));
    }

    public function testMultipleExtends()
    {
        $container = new Container;
        $container['foo'] = 'foo';
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });
        $container->extend('foo', function ($old, $container) {
            return $old.'baz';
        });

        $this->assertEquals('foobarbaz', $container->make('foo'));
    }

    public function testBindingAnInstanceReturnsTheInstance()
    {
        $container = new Container;

        $bound = new stdClass;
        $resolved = $container->instance('foo', $bound);

        $this->assertSame($bound, $resolved);
    }

    public function testExtendInstancesArePreserved()
    {
        $container = new Container;
        $container->bind('foo', function () {
            $obj = new stdClass;
            $obj->foo = 'bar';

            return $obj;
        });
        $obj = new stdClass;
        $obj->foo = 'foo';
        $container->instance('foo', $obj);
        $container->extend('foo', function ($obj, $container) {
            $obj->bar = 'baz';

            return $obj;
        });
        $container->extend('foo', function ($obj, $container) {
            $obj->baz = 'foo';

            return $obj;
        });

        $this->assertEquals('foo', $container->make('foo')->foo);
        $this->assertEquals('baz', $container->make('foo')->bar);
        $this->assertEquals('foo', $container->make('foo')->baz);
    }

    public function testExtendIsLazyInitialized()
    {
        ContainerLazyExtendStub::$initialized = false;

        $container = new Container;
        $container->bind('Illuminate\Tests\Container\ContainerLazyExtendStub');
        $container->extend('Illuminate\Tests\Container\ContainerLazyExtendStub', function ($obj, $container) {
            $obj->init();

            return $obj;
        });
        $this->assertFalse(ContainerLazyExtendStub::$initialized);
        $container->make('Illuminate\Tests\Container\ContainerLazyExtendStub');
        $this->assertTrue(ContainerLazyExtendStub::$initialized);
    }

    public function testExtendCanBeCalledBeforeBind()
    {
        $container = new Container;
        $container->extend('foo', function ($old, $container) {
            return $old.'bar';
        });
        $container['foo'] = 'foo';

        $this->assertEquals('foobar', $container->make('foo'));
    }

    public function testExtendInstanceRebindingCallback()
    {
        $_SERVER['_test_rebind'] = false;

        $container = new Container;
        $container->rebinding('foo', function () {
            $_SERVER['_test_rebind'] = true;
        });

        $obj = new stdClass;
        $container->instance('foo', $obj);

        $container->extend('foo', function ($obj, $container) {
            return $obj;
        });

        $this->assertTrue($_SERVER['_test_rebind']);
    }

    public function testExtendBindRebindingCallback()
    {
        $_SERVER['_test_rebind'] = false;

        $container = new Container;
        $container->rebinding('foo', function () {
            $_SERVER['_test_rebind'] = true;
        });
        $container->bind('foo', function () {
            $obj = new stdClass;

            return $obj;
        });

        $this->assertFalse($_SERVER['_test_rebind']);

        $container->make('foo');

        $container->extend('foo', function ($obj, $container) {
            return $obj;
        });

        $this->assertTrue($_SERVER['_test_rebind']);
    }

    public function testUnsetExtend()
    {
        $container = new Container;
        $container->bind('foo', function () {
            $obj = new stdClass;
            $obj->foo = 'bar';

            return $obj;
        });

        $container->extend('foo', function ($obj, $container) {
            $obj->bar = 'baz';

            return $obj;
        });

        unset($container['foo']);
        $container->forgetExtenders('foo');

        $container->bind('foo', function () {
            return 'foo';
        });

        $this->assertEquals('foo', $container->make('foo'));
    }

    public function testResolutionOfDefaultParameters()
    {
        $container = new Container;
        $instance = $container->make('Illuminate\Tests\Container\ContainerDefaultValueStub');
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $instance->stub);
        $this->assertEquals('taylor', $instance->default);
    }

    public function testResolvingCallbacksAreCalledForSpecificAbstracts()
    {
        $container = new Container;
        $container->resolving('foo', function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testResolvingCallbacksAreCalled()
    {
        $container = new Container;
        $container->resolving(function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testResolvingCallbacksAreCalledForType()
    {
        $container = new Container;
        $container->resolving('stdClass', function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
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
        });
        $container->rebinding('foo', function () {
            $_SERVER['__test.rebind'] = true;
        });
        $container->bind('foo', function () {
        });

        $this->assertTrue($_SERVER['__test.rebind']);
    }

    public function testReboundListenersOnInstances()
    {
        unset($_SERVER['__test.rebind']);

        $container = new Container;
        $container->instance('foo', function () {
        });
        $container->rebinding('foo', function () {
            $_SERVER['__test.rebind'] = true;
        });
        $container->instance('foo', function () {
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
        $container->make('Illuminate\Tests\Container\ContainerMixedPrimitiveStub', []);
    }

    /**
     * @expectedException \Illuminate\Contracts\Container\BindingResolutionException
     * @expectedExceptionMessage Target [Illuminate\Tests\Container\IContainerContractStub] is not instantiable.
     */
    public function testBindingResolutionExceptionMessage()
    {
        $container = new Container;
        $container->make('Illuminate\Tests\Container\IContainerContractStub', []);
    }

    /**
     * @expectedException \Illuminate\Contracts\Container\BindingResolutionException
     * @expectedExceptionMessage Target [Illuminate\Tests\Container\IContainerContractStub] is not instantiable while building [Illuminate\Tests\Container\ContainerTestContextInjectOne].
     */
    public function testBindingResolutionExceptionMessageIncludesBuildStack()
    {
        $container = new Container;
        $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne', []);
    }

    public function testCallWithDependencies()
    {
        $container = new Container;
        $result = $container->call(function (stdClass $foo, $bar = []) {
            return func_get_args();
        });

        $this->assertInstanceOf('stdClass', $result[0]);
        $this->assertEquals([], $result[1]);

        $result = $container->call(function (stdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'taylor']);

        $this->assertInstanceOf('stdClass', $result[0]);
        $this->assertEquals('taylor', $result[1]);

        /*
         * Wrap a function...
         */
        $result = $container->wrap(function (stdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'taylor']);

        $this->assertInstanceOf('Closure', $result);
        $result = $result();

        $this->assertInstanceOf('stdClass', $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    /**
     * @expectedException \ReflectionException
     * @expectedExceptionMessage Function ContainerTestCallStub() does not exist
     */
    public function testCallWithAtSignBasedClassReferencesWithoutMethodThrowsException()
    {
        $container = new Container;
        $result = $container->call('ContainerTestCallStub');
    }

    public function testCallWithAtSignBasedClassReferences()
    {
        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerTestCallStub@work', ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerTestCallStub@inject');
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $result[0]);
        $this->assertEquals('taylor', $result[1]);

        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerTestCallStub@inject', ['default' => 'foo']);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $result[0]);
        $this->assertEquals('foo', $result[1]);

        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerTestCallStub', ['foo', 'bar'], 'work');
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testCallWithCallableArray()
    {
        $container = new Container;
        $stub = new ContainerTestCallStub;
        $result = $container->call([$stub, 'work'], ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testCallWithStaticMethodNameString()
    {
        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerStaticMethodStub::inject');
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    public function testCallWithGlobalMethodName()
    {
        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\containerTestInject');
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    public function testCallWithBoundMethod()
    {
        $container = new Container;
        $container->bindMethod('Illuminate\Tests\Container\ContainerTestCallStub@unresolvable', function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call('Illuminate\Tests\Container\ContainerTestCallStub@unresolvable');
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $container->bindMethod('Illuminate\Tests\Container\ContainerTestCallStub@unresolvable', function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call([new ContainerTestCallStub, 'unresolvable']);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testContainerCanInjectDifferentImplementationsDependingOnContext()
    {
        $container = new Container;

        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStub');
        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectTwo')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $one = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $two = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectTwo');

        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $one->impl);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStubTwo', $two->impl);

        /*
         * Test With Closures
         */
        $container = new Container;

        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStub');
        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectTwo')->needs('Illuminate\Tests\Container\IContainerContractStub')->give(function ($container) {
            return $container->make('Illuminate\Tests\Container\ContainerImplementationStubTwo');
        });

        $one = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $two = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectTwo');

        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $one->impl);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStubTwo', $two->impl);
    }

    public function testContextualBindingWorksForExistingInstancedBindings()
    {
        $container = new Container;

        $container->instance('Illuminate\Tests\Container\IContainerContractStub', new ContainerImplementationStub);

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextualBindingWorksForNewlyInstancedBindings()
    {
        $container = new Container;

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $container->instance('Illuminate\Tests\Container\IContainerContractStub', new ContainerImplementationStub);

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextualBindingWorksOnExistingAliasedInstances()
    {
        $container = new Container;

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', 'Illuminate\Tests\Container\IContainerContractStub');

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedInstances()
    {
        $container = new Container;

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', 'Illuminate\Tests\Container\IContainerContractStub');

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedBindings()
    {
        $container = new Container;

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $container->bind('stub', ContainerImplementationStub::class);
        $container->alias('stub', 'Illuminate\Tests\Container\IContainerContractStub');

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextualBindingDoesntOverrideNonContextualResolution()
    {
        $container = new Container;

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', 'Illuminate\Tests\Container\IContainerContractStub');

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectTwo')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStubTwo',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectTwo')->impl
        );

        $this->assertInstanceOf(
            'Illuminate\Tests\Container\ContainerImplementationStub',
            $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne')->impl
        );
    }

    public function testContextuallyBoundInstancesAreNotUnnecessarilyRecreated()
    {
        ContainerTestContextInjectInstantiations::$instantiations = 0;

        $container = new Container;

        $container->instance('Illuminate\Tests\Container\IContainerContractStub', new ContainerImplementationStub);
        $container->instance('Illuminate\Tests\Container\ContainerTestContextInjectInstantiations', new ContainerTestContextInjectInstantiations);

        $this->assertEquals(1, ContainerTestContextInjectInstantiations::$instantiations);

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('Illuminate\Tests\Container\IContainerContractStub')->give('Illuminate\Tests\Container\ContainerTestContextInjectInstantiations');

        $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');

        $this->assertEquals(1, ContainerTestContextInjectInstantiations::$instantiations);
    }

    public function testContainerTags()
    {
        $container = new Container;
        $container->tag('Illuminate\Tests\Container\ContainerImplementationStub', 'foo', 'bar');
        $container->tag('Illuminate\Tests\Container\ContainerImplementationStubTwo', ['foo']);

        $this->assertCount(1, $container->tagged('bar'));
        $this->assertCount(2, $container->tagged('foo'));
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $container->tagged('foo')[0]);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $container->tagged('bar')[0]);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStubTwo', $container->tagged('foo')[1]);

        $container = new Container;
        $container->tag(['Illuminate\Tests\Container\ContainerImplementationStub', 'Illuminate\Tests\Container\ContainerImplementationStubTwo'], ['foo']);
        $this->assertCount(2, $container->tagged('foo'));
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $container->tagged('foo')[0]);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStubTwo', $container->tagged('foo')[1]);

        $this->assertEmpty($container->tagged('this_tag_does_not_exist'));
    }

    public function testForgetInstanceForgetsInstance()
    {
        $container = new Container;
        $containerConcreteStub = new ContainerConcreteStub;
        $container->instance('Illuminate\Tests\Container\ContainerConcreteStub', $containerConcreteStub);
        $this->assertTrue($container->isShared('Illuminate\Tests\Container\ContainerConcreteStub'));
        $container->forgetInstance('Illuminate\Tests\Container\ContainerConcreteStub');
        $this->assertFalse($container->isShared('Illuminate\Tests\Container\ContainerConcreteStub'));
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
        $concreteStubInstance = $container->make('ConcreteStub');
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

        $concreteStubInstance = $container->make('ConcreteStub');

        $this->assertTrue($container->resolved('ConcreteStub'));
        $this->assertTrue($container->resolved('foo'));
    }

    public function testGetAlias()
    {
        $container = new Container;
        $container->alias('ConcreteStub', 'foo');
        $this->assertEquals($container->getAlias('foo'), 'ConcreteStub');
    }

    public function testContainerCanInjectSimpleVariable()
    {
        $container = new Container;
        $container->when('Illuminate\Tests\Container\ContainerInjectVariableStub')->needs('$something')->give(100);
        $instance = $container->make('Illuminate\Tests\Container\ContainerInjectVariableStub');
        $this->assertEquals(100, $instance->something);

        $container = new Container;
        $container->when('Illuminate\Tests\Container\ContainerInjectVariableStub')->needs('$something')->give(function ($container) {
            return $container->make('Illuminate\Tests\Container\ContainerConcreteStub');
        });
        $instance = $container->make('Illuminate\Tests\Container\ContainerInjectVariableStub');
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerConcreteStub', $instance->something);
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

    public function testExtensionWorksOnAliasedBindings()
    {
        $container = new Container;
        $container->singleton('something', function () {
            return 'some value';
        });
        $container->alias('something', 'something-alias');
        $container->extend('something-alias', function ($value) {
            return $value.' extended';
        });

        $this->assertEquals('some value extended', $container->make('something'));
    }

    public function testContextualBindingWorksWithAliasedTargets()
    {
        $container = new Container;

        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
        $container->alias('Illuminate\Tests\Container\IContainerContractStub', 'interface-stub');

        $container->alias('Illuminate\Tests\Container\ContainerImplementationStub', 'stub-1');

        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectOne')->needs('interface-stub')->give('stub-1');
        $container->when('Illuminate\Tests\Container\ContainerTestContextInjectTwo')->needs('interface-stub')->give('Illuminate\Tests\Container\ContainerImplementationStubTwo');

        $one = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectOne');
        $two = $container->make('Illuminate\Tests\Container\ContainerTestContextInjectTwo');

        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStub', $one->impl);
        $this->assertInstanceOf('Illuminate\Tests\Container\ContainerImplementationStubTwo', $two->impl);
    }

    public function testResolvingCallbacksShouldBeFiredWhenCalledWithAliases()
    {
        $container = new Container;
        $container->alias('stdClass', 'std');
        $container->resolving('std', function ($object) {
            return $object->name = 'taylor';
        });
        $container->bind('foo', function () {
            return new stdClass;
        });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
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
        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
        $this->assertInstanceOf(ContainerDependentStub::class, $container->build(ContainerDependentStub::class));
    }

    public function testContainerKnowsEntry()
    {
        $container = new Container;
        $container->bind('Illuminate\Tests\Container\IContainerContractStub', 'Illuminate\Tests\Container\ContainerImplementationStub');
        $this->assertTrue($container->has('Illuminate\Tests\Container\IContainerContractStub'));
    }

    public function testContainerCanBindAnyWord()
    {
        $container = new Container;
        $container->bind('Taylor', stdClass::class);
        $this->assertInstanceOf(stdClass::class, $container->get('Taylor'));
    }

    /**
     * @expectedException \Illuminate\Container\EntryNotFoundException
     * @expectedExceptionMessage
     */
    public function testUnknownEntryThrowsException()
    {
        $container = new Container;
        $container->get('Taylor');
    }
}

class ContainerConcreteStub
{
}

interface IContainerContractStub
{
}

class ContainerImplementationStub implements IContainerContractStub
{
}

class ContainerImplementationStubTwo implements IContainerContractStub
{
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

class ContainerConstructorParameterLoggingStub
{
    public $receivedParameters;

    public function __construct($first, $second)
    {
        $this->receivedParameters = func_get_args();
    }
}

class ContainerLazyExtendStub
{
    public static $initialized = false;

    public function init()
    {
        static::$initialized = true;
    }
}

class ContainerTestCallStub
{
    public function work()
    {
        return func_get_args();
    }

    public function inject(ContainerConcreteStub $stub, $default = 'taylor')
    {
        return func_get_args();
    }

    public function unresolvable($foo, $bar)
    {
        return func_get_args();
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

class ContainerStaticMethodStub
{
    public static function inject(ContainerConcreteStub $stub, $default = 'taylor')
    {
        return func_get_args();
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

function containerTestInject(ContainerConcreteStub $stub, $default = 'taylor')
{
    return func_get_args();
}

class ContainerTestContextInjectInstantiations implements IContainerContractStub
{
    public static $instantiations;

    public function __construct()
    {
        static::$instantiations++;
    }
}
