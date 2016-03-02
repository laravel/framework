<?php

use Illuminate\Container\Container;

class ContainerContainerTest extends PHPUnit_Framework_TestCase
{
    public function testClosureResolution()
    {
        $container = new Container;
        $container->bind('name', function () { return 'Taylor'; });
        $this->assertEquals('Taylor', $container->make('name'));
    }

    public function testBindIfDoesntRegisterIfServiceAlreadyRegistered()
    {
        $container = new Container;
        $container->bind('name', function () { return 'Taylor'; });
        $container->bindIf('name', function () { return 'Dayle'; });

        $this->assertEquals('Taylor', $container->make('name'));
    }

    public function testSharedClosureResolution()
    {
        $container = new Container;
        $class = new stdClass;
        $container->singleton('class', function () use ($class) { return $class; });
        $this->assertSame($class, $container->make('class'));
    }

    public function testAutoConcreteResolution()
    {
        $container = new Container;
        $this->assertInstanceOf('ContainerConcreteStub', $container->make('ContainerConcreteStub'));
    }

    public function testSlashesAreHandled()
    {
        $container = new Container;
        $container->bind('\Foo', function () { return 'hello'; });
        $this->assertEquals('hello', $container->make('Foo'));
    }

    public function testParametersCanOverrideDependencies()
    {
        $container = new Container;
        $stub = new ContainerDependentStub($mock = $this->getMock('IContainerContractStub'));
        $resolved = $container->make('ContainerNestedDependentStub', [$stub]);
        $this->assertInstanceOf('ContainerNestedDependentStub', $resolved);
        $this->assertEquals($mock, $resolved->inner->impl);
    }

    public function testSharedConcreteResolution()
    {
        $container = new Container;
        $container->singleton('ContainerConcreteStub');

        $var1 = $container->make('ContainerConcreteStub');
        $var2 = $container->make('ContainerConcreteStub');
        $this->assertSame($var1, $var2);
    }

    public function testAbstractToConcreteResolution()
    {
        $container = new Container;
        $container->bind('IContainerContractStub', 'ContainerImplementationStub');
        $class = $container->make('ContainerDependentStub');
        $this->assertInstanceOf('ContainerImplementationStub', $class->impl);
    }

    public function testNestedDependencyResolution()
    {
        $container = new Container;
        $container->bind('IContainerContractStub', 'ContainerImplementationStub');
        $class = $container->make('ContainerNestedDependentStub');
        $this->assertInstanceOf('ContainerDependentStub', $class->inner);
        $this->assertInstanceOf('ContainerImplementationStub', $class->inner->impl);
    }

    public function testContainerIsPassedToResolvers()
    {
        $container = new Container;
        $container->bind('something', function ($c) { return $c; });
        $c = $container->make('something');
        $this->assertSame($c, $container);
    }

    public function testArrayAccess()
    {
        $container = new Container;
        $container['something'] = function () { return 'foo'; };
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
        $this->assertEquals('bar', $container->make('foo'));
        $this->assertEquals('bar', $container->make('baz'));
        $container->bind(['bam' => 'boom'], function () { return 'pow'; });
        $this->assertEquals('pow', $container->make('bam'));
        $this->assertEquals('pow', $container->make('boom'));
        $container->instance(['zoom' => 'zing'], 'wow');
        $this->assertEquals('wow', $container->make('zoom'));
        $this->assertEquals('wow', $container->make('zing'));
    }

    public function testShareMethod()
    {
        $container = new Container;
        $closure = $container->share(function () { return new stdClass; });
        $class1 = $closure($container);
        $class2 = $closure($container);
        $this->assertSame($class1, $class2);
    }

    public function testBindingsCanBeOverridden()
    {
        $container = new Container;
        $container['foo'] = 'bar';
        $foo = $container['foo'];
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

        $container['foo'] = $container->share(function () {
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

    public function testExtendInstancesArePreserved()
    {
        $container = new Container;
        $container->bind('foo', function () { $obj = new StdClass; $obj->foo = 'bar';

return $obj; });
        $obj = new StdClass;
        $obj->foo = 'foo';
        $container->instance('foo', $obj);
        $container->extend('foo', function ($obj, $container) { $obj->bar = 'baz';

return $obj; });
        $container->extend('foo', function ($obj, $container) { $obj->baz = 'foo';

return $obj; });
        $this->assertEquals('foo', $container->make('foo')->foo);
    }

    public function testExtendIsLazyInitialized()
    {
        $container = new Container;
        $container->bind('ContainerLazyExtendStub');
        $container->extend('ContainerLazyExtendStub', function ($obj, $container) { $obj->init();

return $obj; });
        $this->assertFalse(ContainerLazyExtendStub::$initialized);
        $container->make('ContainerLazyExtendStub');
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

    public function testParametersCanBePassedThroughToClosure()
    {
        $container = new Container;
        $container->bind('foo', function ($c, $parameters) {
            return $parameters;
        });

        $this->assertEquals([1, 2, 3], $container->make('foo', [1, 2, 3]));
    }

    public function testResolutionOfDefaultParameters()
    {
        $container = new Container;
        $instance = $container->make('ContainerDefaultValueStub');
        $this->assertInstanceOf('ContainerConcreteStub', $instance->stub);
        $this->assertEquals('taylor', $instance->default);
    }

    public function testResolvingCallbacksAreCalledForSpecificAbstracts()
    {
        $container = new Container;
        $container->resolving('foo', function ($object) { return $object->name = 'taylor'; });
        $container->bind('foo', function () { return new StdClass; });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testResolvingCallbacksAreCalled()
    {
        $container = new Container;
        $container->resolving(function ($object) { return $object->name = 'taylor'; });
        $container->bind('foo', function () { return new StdClass; });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testResolvingCallbacksAreCalledForType()
    {
        $container = new Container;
        $container->resolving('StdClass', function ($object) { return $object->name = 'taylor'; });
        $container->bind('foo', function () { return new StdClass; });
        $instance = $container->make('foo');

        $this->assertEquals('taylor', $instance->name);
    }

    public function testUnsetRemoveBoundInstances()
    {
        $container = new Container;
        $container->instance('object', new StdClass);
        unset($container['object']);

        $this->assertFalse($container->bound('object'));
    }

    public function testReboundListeners()
    {
        unset($_SERVER['__test.rebind']);

        $container = new Container;
        $container->bind('foo', function () {});
        $container->rebinding('foo', function () { $_SERVER['__test.rebind'] = true; });
        $container->bind('foo', function () {});

        $this->assertTrue($_SERVER['__test.rebind']);
    }

    public function testReboundListenersOnInstances()
    {
        unset($_SERVER['__test.rebind']);

        $container = new Container;
        $container->instance('foo', function () {});
        $container->rebinding('foo', function () { $_SERVER['__test.rebind'] = true; });
        $container->instance('foo', function () {});

        $this->assertTrue($_SERVER['__test.rebind']);
    }

    public function testPassingSomePrimitiveParameters()
    {
        $container = new Container;
        $value = $container->make('ContainerMixedPrimitiveStub', ['first' => 'taylor', 'last' => 'otwell']);
        $this->assertInstanceOf('ContainerMixedPrimitiveStub', $value);
        $this->assertEquals('taylor', $value->first);
        $this->assertEquals('otwell', $value->last);
        $this->assertInstanceOf('ContainerConcreteStub', $value->stub);

        $container = new Container;
        $value = $container->make('ContainerMixedPrimitiveStub', [0 => 'taylor', 2 => 'otwell']);
        $this->assertInstanceOf('ContainerMixedPrimitiveStub', $value);
        $this->assertEquals('taylor', $value->first);
        $this->assertEquals('otwell', $value->last);
        $this->assertInstanceOf('ContainerConcreteStub', $value->stub);
    }

    public function testCreatingBoundConcreteClassPassesParameters()
    {
        $container = new Container;
        $container->bind('TestAbstractClass', 'ContainerConstructorParameterLoggingStub');
        $parameters = ['First', 'Second'];
        $instance = $container->make('TestAbstractClass', $parameters);
        $this->assertEquals($parameters, $instance->receivedParameters);
    }

    /**
     * @expectedException Illuminate\Contracts\Container\BindingResolutionException
     * @expectedExceptionMessage Unresolvable dependency resolving [Parameter #0 [ <required> $first ]] in class ContainerMixedPrimitiveStub
     */
    public function testInternalClassWithDefaultParameters()
    {
        $container = new Container;
        $container->make('ContainerMixedPrimitiveStub', []);
    }

    /**
     * @expectedException Illuminate\Contracts\Container\BindingResolutionException
     * @expectedExceptionMessage Target [IContainerContractStub] is not instantiable.
     */
    public function testBindingResolutionExceptionMessage()
    {
        $container = new Container;
        $container->make('IContainerContractStub', []);
    }

    /**
     * @expectedException Illuminate\Contracts\Container\BindingResolutionException
     * @expectedExceptionMessage Target [IContainerContractStub] is not instantiable while building [ContainerTestContextInjectOne].
     */
    public function testBindingResolutionExceptionMessageIncludesBuildStack()
    {
        $container = new Container;
        $container->make('ContainerTestContextInjectOne', []);
    }

    public function testCallWithDependencies()
    {
        $container = new Container;
        $result = $container->call(function (StdClass $foo, $bar = []) {
            return func_get_args();
        });

        $this->assertInstanceOf('stdClass', $result[0]);
        $this->assertEquals([], $result[1]);

        $result = $container->call(function (StdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'taylor']);

        $this->assertInstanceOf('stdClass', $result[0]);
        $this->assertEquals('taylor', $result[1]);

        /*
         * Wrap a function...
         */
        $result = $container->wrap(function (StdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'taylor']);

        $this->assertInstanceOf('Closure', $result);
        $result = $result();

        $this->assertInstanceOf('stdClass', $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    /**
     * @expectedException ReflectionException
     */
    public function testCallWithAtSignBasedClassReferencesWithoutMethodThrowsException()
    {
        $container = new Container;
        $result = $container->call('ContainerTestCallStub');
    }

    public function testCallWithAtSignBasedClassReferences()
    {
        $container = new Container;
        $result = $container->call('ContainerTestCallStub@work', ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $result = $container->call('ContainerTestCallStub@inject');
        $this->assertInstanceOf('ContainerConcreteStub', $result[0]);
        $this->assertEquals('taylor', $result[1]);

        $container = new Container;
        $result = $container->call('ContainerTestCallStub@inject', ['default' => 'foo']);
        $this->assertInstanceOf('ContainerConcreteStub', $result[0]);
        $this->assertEquals('foo', $result[1]);

        $container = new Container;
        $result = $container->call('ContainerTestCallStub', ['foo', 'bar'], 'work');
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testCallWithCallableArray()
    {
        $container = new Container;
        $stub = new ContainerTestCallStub();
        $result = $container->call([$stub, 'work'], ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testCallWithStaticMethodNameString()
    {
        $container = new Container;
        $result = $container->call('ContainerStaticMethodStub::inject');
        $this->assertInstanceOf('ContainerConcreteStub', $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    public function testCallWithGlobalMethodName()
    {
        $container = new Container;
        $result = $container->call('containerTestInject');
        $this->assertInstanceOf('ContainerConcreteStub', $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    public function testContainerCanInjectDifferentImplementationsDependingOnContext()
    {
        $container = new Container;

        $container->bind('IContainerContractStub', 'ContainerImplementationStub');

        $container->when('ContainerTestContextInjectOne')->needs('IContainerContractStub')->give('ContainerImplementationStub');
        $container->when('ContainerTestContextInjectTwo')->needs('IContainerContractStub')->give('ContainerImplementationStubTwo');

        $one = $container->make('ContainerTestContextInjectOne');
        $two = $container->make('ContainerTestContextInjectTwo');

        $this->assertInstanceOf('ContainerImplementationStub', $one->impl);
        $this->assertInstanceOf('ContainerImplementationStubTwo', $two->impl);

        /*
         * Test With Closures
         */
        $container = new Container;

        $container->bind('IContainerContractStub', 'ContainerImplementationStub');

        $container->when('ContainerTestContextInjectOne')->needs('IContainerContractStub')->give('ContainerImplementationStub');
        $container->when('ContainerTestContextInjectTwo')->needs('IContainerContractStub')->give(function ($container) {
            return $container->make('ContainerImplementationStubTwo');
        });

        $one = $container->make('ContainerTestContextInjectOne');
        $two = $container->make('ContainerTestContextInjectTwo');

        $this->assertInstanceOf('ContainerImplementationStub', $one->impl);
        $this->assertInstanceOf('ContainerImplementationStubTwo', $two->impl);
    }

    public function testContextualBindingWorksRegardlessOfLeadingBackslash()
    {
        $container = new Container;

        $container->bind('IContainerContractStub', 'ContainerImplementationStub');

        $container->when('\ContainerTestContextInjectOne')->needs('IContainerContractStub')->give('ContainerImplementationStubTwo');
        $container->when('ContainerTestContextInjectTwo')->needs('\IContainerContractStub')->give('ContainerImplementationStubTwo');

        $this->assertInstanceOf(
            'ContainerImplementationStubTwo',
            $container->make('ContainerTestContextInjectOne')->impl
        );

        $this->assertInstanceOf(
            'ContainerImplementationStubTwo',
            $container->make('ContainerTestContextInjectTwo')->impl
        );

        $this->assertInstanceOf(
            'ContainerImplementationStubTwo',
            $container->make('\ContainerTestContextInjectTwo')->impl
        );
    }

    public function testContainerTags()
    {
        $container = new Container;
        $container->tag('ContainerImplementationStub', 'foo', 'bar');
        $container->tag('ContainerImplementationStubTwo', ['foo']);

        $this->assertCount(1, $container->tagged('bar'));
        $this->assertCount(2, $container->tagged('foo'));
        $this->assertInstanceOf('ContainerImplementationStub', $container->tagged('foo')[0]);
        $this->assertInstanceOf('ContainerImplementationStub', $container->tagged('bar')[0]);
        $this->assertInstanceOf('ContainerImplementationStubTwo', $container->tagged('foo')[1]);

        $container = new Container;
        $container->tag(['ContainerImplementationStub', 'ContainerImplementationStubTwo'], ['foo']);
        $this->assertCount(2, $container->tagged('foo'));
        $this->assertInstanceOf('ContainerImplementationStub', $container->tagged('foo')[0]);
        $this->assertInstanceOf('ContainerImplementationStubTwo', $container->tagged('foo')[1]);

        $this->assertEmpty($container->tagged('this_tag_does_not_exist'));
    }

    public function testForgetInstanceForgetsInstance()
    {
        $container = new Container;
        $containerConcreteStub = new ContainerConcreteStub;
        $container->instance('ContainerConcreteStub', $containerConcreteStub);
        $this->assertTrue($container->isShared('ContainerConcreteStub'));
        $container->forgetInstance('ContainerConcreteStub');
        $this->assertFalse($container->isShared('ContainerConcreteStub'));
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
        $container->bind('ConcreteStub', function () { return new ContainerConcreteStub; }, true);
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
        $container->bind('ConcreteStub', function () { return new ContainerConcreteStub; }, true);
        $container->alias('ConcreteStub', 'foo');

        $this->assertFalse($container->resolved('ConcreteStub'));
        $this->assertFalse($container->resolved('foo'));

        $concreteStubInstance = $container->make('ConcreteStub');

        $this->assertTrue($container->resolved('ConcreteStub'));
        $this->assertTrue($container->resolved('foo'));
    }

    public function testContainerCanInjectSimpleVariable()
    {
        $container = new Container;
        $container->when('ContainerInjectVariableStub')->needs('$something')->give(100);
        $instance = $container->make('ContainerInjectVariableStub');
        $this->assertEquals(100, $instance->something);

        $container = new Container;
        $container->when('ContainerInjectVariableStub')->needs('$something')->give(function ($container) {
            return $container->make('ContainerConcreteStub');
        });
        $instance = $container->make('ContainerInjectVariableStub');
        $this->assertInstanceOf('ContainerConcreteStub', $instance->something);
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

function containerTestInject(ContainerConcreteStub $stub, $default = 'taylor')
{
    return func_get_args();
}
