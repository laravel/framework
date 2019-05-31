<?php

namespace Illuminate\Tests\Container;

use Closure;
use stdClass;
use ReflectionException;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;

class ContainerCallTest extends TestCase
{
    public function testCallWithAtSignBasedClassReferencesWithoutMethodThrowsException()
    {
        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('Function ContainerTestCallStub() does not exist');

        $container = new Container;
        $container->call('ContainerTestCallStub');
    }

    public function testCallWithAtSignBasedClassReferences()
    {
        $container = new Container;
        $result = $container->call(ContainerTestCallStub::class.'@work', ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $result = $container->call(ContainerTestCallStub::class.'@inject');
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);

        $container = new Container;
        $result = $container->call(ContainerTestCallStub::class.'@inject', ['default' => 'foo']);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertEquals('foo', $result[1]);

        $container = new Container;
        $result = $container->call(ContainerTestCallStub::class, ['foo', 'bar'], 'work');
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testCallWithCallableArray()
    {
        $container = new Container;
        $stub = new ContainerTestCallStub;
        $result = $container->call([$stub, 'work'], ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $stub = new ContainerTestCallStub;
        $result = $container->call([$stub, 'work'], ['b' => 'bar', 'a' => 'foo']);
        $this->assertEquals(['bar', 'foo'], $result);
    }

    public function testCallWithStaticMethodNameString()
    {
        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerStaticMethodStub::inject');
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);

        $container = new Container;
        $result = $container->call([new ContainerTestCallStub, 'inject'], ['baz']);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertEquals('baz', $result[1]);
        $container = new Container;
        $object = new ContainerCallConcreteStub;
        $result = $container->call([new ContainerTestCallStub, 'inject'], [$object, 'foo']);
        $this->assertSame($object, $result[0]);
        $this->assertEquals('foo', $result[1]);
    }

    public function testCallWithGlobalMethodName()
    {
        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\containerTestInject');
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    public function testCallWithBoundMethod()
    {
        $container = new Container;
        $container->bindMethod(ContainerTestCallStub::class.'@unresolvable', function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call(ContainerTestCallStub::class.'@unresolvable');
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $container->bindMethod(ContainerTestCallStub::class.'@unresolvable', function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call([new ContainerTestCallStub, 'unresolvable']);
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $result = $container->call([new ContainerTestCallStub, 'inject'], ['_stub' => 'foo', 'default' => 'bar']);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertEquals('bar', $result[1]);

        $container = new Container;
        $result = $container->call([new ContainerTestCallStub, 'inject'], ['_stub' => 'foo']);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    public function testBindMethodAcceptsAnArray()
    {
        $container = new Container;
        $container->bindMethod([ContainerTestCallStub::class, 'unresolvable'], function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call(ContainerTestCallStub::class.'@unresolvable');
        $this->assertEquals(['foo', 'bar'], $result);

        $container = new Container;
        $container->bindMethod([ContainerTestCallStub::class, 'unresolvable'], function ($stub) {
            return $stub->unresolvable('foo', 'bar');
        });
        $result = $container->call([new ContainerTestCallStub, 'unresolvable']);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testClosureCallWithInjectedDependency()
    {
        $container = new Container;
        $result = $container->call(function (ContainerCallConcreteStub $stub) {
            return $stub;
        }, ['foo' => 'bar']);

        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result);
        $result = $container->call(function (ContainerCallConcreteStub $stub) {
            return $stub;
        }, ['foo' => 'bar', 'stub' => new ContainerCallConcreteStub()]);

        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result);
        $obj = new ContainerCallConcreteStub;
        $result = $container->call(function (ContainerCallConcreteStub $stub) {
            return $stub;
        }, [$obj]);
        $this->assertSame($obj, $result);

        $obj = new ContainerCallConcreteStub;
        $result = $container->call(function (ContainerCallConcreteStub $stub, $baz = 'taylor') {
            return [$stub, $baz];
        }, ['foo' => 'bar', 'stub' => $obj]);
        $this->assertSame($obj, $result[0]);
        $this->assertEquals('taylor', $result[1]);
        $obj = new ContainerCallConcreteStub;
        $result = $container->call(function ($foo, ContainerCallConcreteStub $stub) {
            return [$foo, $stub];
        }, ['foo', $obj]);
        $this->assertEquals('foo', $result[0]);
        $this->assertSame($obj, $result[1]);

        $result = $container->call(function ($foo = 'default foo', ContainerCallConcreteStub $stub = null) {
            return [$foo, $stub];
        }, []);
        $this->assertEquals('default foo', $result[0]);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[1]);
    }

    public function testCallWithDependencies()
    {
        $container = new Container;
        $result = $container->call(function (stdClass $foo, $bar = []) {
            return func_get_args();
        });

        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertEquals([], $result[1]);

        $result = $container->call(function (stdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'taylor']);

        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);

        $stub = new ContainerCallConcreteStub;
        $result = $container->call(function (stdClass $foo, ContainerCallConcreteStub $bar) {
            return func_get_args();
        }, [ContainerCallConcreteStub::class => $stub]);

        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertSame($stub, $result[1]);

        /*
         * Wrap a function...
         */
        $result = $container->wrap(function (stdClass $foo, $bar = []) {
            return func_get_args();
        }, ['bar' => 'taylor']);

        $this->assertInstanceOf(Closure::class, $result);
        $result = $result();

        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertEquals('taylor', $result[1]);
    }

    public function testTypeErrorHappensWhenWrongDataIsPassed()
    {
        $callable = function (ContainerCallConcreteStub $stub) {
        };
        $this->expectException(\TypeError::class);
        (new Container)->call($callable, ['foo']);
    }

    public function testWithDefaultParametersIndexedArraySyntax()
    {
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaulty', ['foo', 'bar', 'baz']);
        $this->assertEquals(['foo', 'bar', 'baz'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaulty');
        $this->assertEquals(['default a', 'default b', 'default c'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaultyBandC', ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar', 'default c'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaultyBandC', ['foo']);
        $this->assertEquals(['foo', 'default b', 'default c'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaultyBandC', ['foo', null]);
        $this->assertEquals(['foo', null, 'default c'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaultyBandC', [null, null, null]);
        $this->assertEquals([null, null, null], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@noDefault', ['foo', 'bar', 'baz', 'foo2', 'bar2', 'baz2']);
        $this->assertEquals(['foo', 'bar', 'baz', 'foo2', 'bar2', 'baz2'], $result);
    }

    public function testWithDefaultParametersAssociativeSyntax()
    {
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaulty', ['a' => 'foo', 'b' => 'bar']);
        $this->assertEquals(['foo', 'bar', 'default c'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaulty', ['b' => 'bar', 'a' => 'foo']);
        $this->assertEquals(['foo', 'bar', 'default c'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaulty', ['c' => 'baz', 'b' => 'bar', 'a' => 'foo']);
        $this->assertEquals(['foo', 'bar', 'baz'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaulty', ['a' => 'foo', 'c' => 'baz', 'b' => 'bar']);
        $this->assertEquals(['foo', 'bar', 'baz'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaultyBandC', ['b' => 'bar', 'a' => 'foo']);
        $this->assertEquals(['foo', 'bar', 'default c'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaultyBandC', ['a' => 'foo']);
        $this->assertEquals(['foo', 'default b', 'default c'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaultyOnlyC', ['a' => 'foo', 'b' => 'bar']);
        $this->assertEquals(['foo', 'bar', 'default c'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@defaultyOnlyC', ['b' => 'bar', 'a' => 'foo']);
        $this->assertEquals(['foo', 'bar', 'default c'], $result);
        $container = new Container;
        $result = $container->call(ContainerTestDefaultyParams::class.'@noDefault', ['a' => 'foo', 'c' => 'baz', 'b' => 'bar']);
        $this->assertEquals(['foo', 'bar', 'baz'], $result);
    }
}

class ContainerTestCallStub
{
    public function work()
    {
        return func_get_args();
    }

    public function inject(ContainerCallConcreteStub $stub, $default = 'taylor')
    {
        return func_get_args();
    }

    public function unresolvable($foo, $bar)
    {
        return func_get_args();
    }
}

class ContainerCallConcreteStub
{
    //
}

function containerTestInject(ContainerCallConcreteStub $stub, $default = 'taylor')
{
    return func_get_args();
}

class ContainerStaticMethodStub
{
    public static function inject(ContainerCallConcreteStub $stub, $default = 'taylor')
    {
        return func_get_args();
    }
}

class ContainerTestDefaultyParams
{
    public function defaulty($a = 'default a', $b = 'default b', $c = 'default c')
    {
        return func_get_args();
    }

    public function defaultyBandC($a, $b = 'default b', $c = 'default c')
    {
        return func_get_args();
    }

    public function defaultyOnlyC($a, $b, $c = 'default c')
    {
        return func_get_args();
    }

    public function noDefault($a, $b, $c)
    {
        return func_get_args();
    }
}
