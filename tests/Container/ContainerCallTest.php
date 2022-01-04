<?php

namespace Illuminate\Tests\Container;

use Closure;
use Error;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerCallTest extends TestCase
{
    public function testCallWithAtSignBasedClassReferencesWithoutMethodThrowsException()
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Call to undefined function ContainerTestCallStub()');

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
        $this->assertSame('taylor', $result[1]);

        $container = new Container;
        $result = $container->call(ContainerTestCallStub::class.'@inject', ['default' => 'foo']);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('foo', $result[1]);

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
    }

    public function testCallWithStaticMethodNameString()
    {
        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\ContainerStaticMethodStub::inject');
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('taylor', $result[1]);
    }

    public function testCallWithGlobalMethodName()
    {
        $container = new Container;
        $result = $container->call('Illuminate\Tests\Container\containerTestInject');
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('taylor', $result[1]);
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
        $this->assertSame('bar', $result[1]);

        $container = new Container;
        $result = $container->call([new ContainerTestCallStub, 'inject'], ['_stub' => 'foo']);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('taylor', $result[1]);
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
        $container->call(function (ContainerCallConcreteStub $stub) {
            //
        }, ['foo' => 'bar']);

        $container->call(function (ContainerCallConcreteStub $stub) {
            //
        }, ['foo' => 'bar', 'stub' => new ContainerCallConcreteStub]);
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
        $this->assertSame('taylor', $result[1]);

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
        $this->assertSame('taylor', $result[1]);
    }

    public function testCallWithVariadicDependency()
    {
        $stub1 = new ContainerCallConcreteStub;
        $stub2 = new ContainerCallConcreteStub;

        $container = new Container;
        $container->bind(ContainerCallConcreteStub::class, function () use ($stub1, $stub2) {
            return [
                $stub1,
                $stub2,
            ];
        });

        $result = $container->call(function (stdClass $foo, ContainerCallConcreteStub ...$bar) {
            return func_get_args();
        });

        $this->assertInstanceOf(stdClass::class, $result[0]);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[1]);
        $this->assertSame($stub1, $result[1]);
        $this->assertSame($stub2, $result[2]);
    }

    public function testCallWithCallableObject()
    {
        $container = new Container;
        $callable = new ContainerCallCallableStub;
        $result = $container->call($callable);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('jeffrey', $result[1]);
    }

    public function testCallWithCallableClassString()
    {
        $container = new Container;
        $result = $container->call(ContainerCallCallableClassStringStub::class);
        $this->assertInstanceOf(ContainerCallConcreteStub::class, $result[0]);
        $this->assertSame('jeffrey', $result[1]);
        $this->assertInstanceOf(ContainerTestCallStub::class, $result[2]);
    }

    public function testCallWithoutRequiredParamsThrowsException()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Unable to resolve dependency [Parameter #0 [ <required> $foo ]] in class Illuminate\Tests\Container\ContainerTestCallStub');

        $container = new Container;
        $container->call(ContainerTestCallStub::class.'@unresolvable');
    }

    public function testCallWithUnnamedParametersThrowsException()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Unable to resolve dependency [Parameter #0 [ <required> $foo ]] in class Illuminate\Tests\Container\ContainerTestCallStub');

        $container = new Container;
        $container->call([new ContainerTestCallStub, 'unresolvable'], ['foo', 'bar']);
    }

    public function testCallWithoutRequiredParamsOnClosureThrowsException()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Unable to resolve dependency [Parameter #0 [ <required> $foo ]] in class Illuminate\Tests\Container\ContainerCallTest');

        $container = new Container;
        $container->call(function ($foo, $bar = 'default') {
            return $foo;
        });
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

class ContainerCallCallableStub
{
    public function __invoke(ContainerCallConcreteStub $stub, $default = 'jeffrey')
    {
        return func_get_args();
    }
}

class ContainerCallCallableClassStringStub
{
    public $stub;

    public $default;

    public function __construct(ContainerCallConcreteStub $stub, $default = 'jeffrey')
    {
        $this->stub = $stub;
        $this->default = $default;
    }

    public function __invoke(ContainerTestCallStub $dependency)
    {
        return [$this->stub, $this->default, $dependency];
    }
}
