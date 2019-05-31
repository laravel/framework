<?php

namespace Illuminate\Tests\Container;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Container\BoundMethod;

class BoundMethodTest extends TestCase
{
    public function testBoundMethodAccessor()
    {
        $defaulty = function ($a, $b = 'default b', $c = 'default c') {
        };
        $args = BoundMethodAccessor::getMethodDependencies(new Container(), $defaulty, ['a', 'b', 'c']);
        $this->assertSame(['a', 'b', 'c'], $args);
        $args = BoundMethodAccessor::getMethodDependencies(new Container(), $defaulty, ['a', 'b']);
        $this->assertSame(['a', 'b', 'default c'], $args);
        $args = BoundMethodAccessor::getMethodDependencies(new Container(), $defaulty, ['a', 'b', null]);
        $this->assertSame(['a', 'b', null], $args);
        $args = BoundMethodAccessor::getMethodDependencies(new Container(), $defaulty, ['a', null]);
        $this->assertSame(['a', null, 'default c'], $args);
        $args = BoundMethodAccessor::getMethodDependencies(new Container(), $defaulty, ['a']);
        $this->assertSame(['a', 'default b', 'default c'], $args);
    }

    public function testExtraNumberOfInputArePassedIntoMethod()
    {
        $callable = function ($a, $b) {
        };
        $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callable, ['a', 'b', 'c', 'd']);
        $this->assertEquals(['a', 'b', 'c', 'd'], $args);
        $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callable, ['a', 'b', 'c', null]);
        $this->assertEquals(['a', 'b', 'c', null], $args);
    }

    public function testCallingWithNoArgs()
    {
        $callee = function () {
        };
        $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['a', 'b', 'c']);
        $this->assertSame(['a', 'b', 'c'], $args);
        $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['key_a' => 'value_a', 'key_b' => 'value_b']);
        $this->assertSame(['key_a' => 'value_a', 'key_b' => 'value_b'], $args);
    }

    public function testCanInjectAtEnd()
    {
        $callee = function ($a, $b = 'default b', ContainerBoundMethodStub $c = null) {
            return [$a, $b, $c];
        };
        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['a']);

        $this->assertEquals('a', $a);
        $this->assertEquals('default b', $b);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $c);
        $this->assertCount(3, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['a', 'b']);

        $this->assertEquals('a', $a);
        $this->assertEquals('b', $b);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $c);

        $this->assertCount(3, $args);
    }

    public function testCanInjectAtMiddle()
    {
        $callee = function ($a, ContainerBoundMethodStub $b, $c = 'default c') {
        };
        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['a' => 'passed a', 'junk' => 'junk']);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertEquals('default c', $c);
        $this->assertArrayNotHasKey(3, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['c' => 'value c', 'junk' => 'junk', 'a' => 'passed a']);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertEquals('value c', $c);
        $this->assertArrayNotHasKey(3, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['passed a', 'value c']);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertEquals('value c', $c);
        $this->assertCount(3, $args);

        $obj = new ContainerBoundMethodStub();
        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['passed a', $obj]);

        $this->assertEquals('passed a', $a);
        $this->assertSame($obj, $b);
        $this->assertEquals('default c', $c);
        $this->assertCount(3, $args);
        $this->assertArrayNotHasKey(3, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['passed a', $obj, 'passed c']);
        $this->assertEquals('passed a', $a);
        $this->assertSame($obj, $b);
        $this->assertEquals('passed c', $c);
        $this->assertArrayNotHasKey(3, $args);
        $stub2 = new ContainerBoundMethodStub2();
        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['passed a', $stub2]);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertSame($stub2, $c);
        $this->assertArrayNotHasKey(3, $args);
    }

    public function testCanInjectTwoAtMiddle()
    {
        $callee = function ($a, ContainerBoundMethodStub $b, ContainerBoundMethodStub2 $c, $d = 'default d') {
        };
        [$a, $b, $c, $d] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['a' => 'passed a', 'junk' => 'junk']);

        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertInstanceOf(ContainerBoundMethodStub2::class, $c);
        $this->assertEquals('default d', $d);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c, $d] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['c' => 'value c', 'junk' => 'junk', 'a' => 'passed a']);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertEquals('value c', $c);
        $this->assertEquals('default d', $d);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c, $d] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['passed a', 'value c']);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertInstanceOf(ContainerBoundMethodStub2::class, $c);
        $this->assertEquals('value c', $d);
        $this->assertArrayNotHasKey(4, $args);

        $obj = new ContainerBoundMethodStub();
        [$a, $b, $c, $d] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['passed a', $obj]);

        $this->assertEquals('passed a', $a);
        $this->assertSame($obj, $b);
        $this->assertInstanceOf(ContainerBoundMethodStub2::class, $c);
        $this->assertEquals('default d', $d);
        $this->assertCount(4, $args);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c, $d] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['passed a', $obj, 'passed c']);
        $this->assertEquals('passed a', $a);
        $this->assertSame($obj, $b);
        $this->assertInstanceOf(ContainerBoundMethodStub2::class, $c);
        $this->assertEquals('passed c', $d);
        $this->assertArrayNotHasKey(4, $args);

        $stub2 = new ContainerBoundMethodStub2();
        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['passed a', $stub2]);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertSame($stub2, $c);
        $this->assertCount(4, $args);

        $stub = new ContainerBoundMethodStub();
        $stub2 = new ContainerBoundMethodStub2();
        [$a, $b, $c, $d] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['passed a', $stub, $stub2]);
        $this->assertEquals('passed a', $a);
        $this->assertSame($stub, $b);
        $this->assertSame($stub2, $c);
        $this->assertEquals('default d', $d);
        $this->assertCount(4, $args);
    }

    public function testCanWorkWithTypeHintedInterFaces()
    {
        $callee = function ($a, ContainerBoundMethodStub $b, ContainerCallAbstractStub $c, $d = 'default d') {
        };

        $stub = new ContainerBoundMethodStub();
        $stub2 = new ContainerBoundMethodStub();
        [$a, $b, $c, $d] = $args = BoundMethodAccessor::getMethodDependencies(new Container(), $callee, ['passed a', $stub, $stub2]);
        $this->assertEquals('passed a', $a);
        $this->assertSame($stub, $b);
        $this->assertSame($stub2, $c);
        $this->assertEquals('default d', $d);
        $this->assertCount(4, $args);

        $app = new Container();
        $app->bind(ContainerCallAbstractStub::class, ContainerBoundMethodStub::class);
        $stub = new ContainerBoundMethodStub();
        $stub2 = new ContainerBoundMethodStub();
        $args = ['passed a', $stub, $stub2];
        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($app, $callee, $args);
        $this->assertEquals('passed a', $a);
        $this->assertSame($stub, $b);
        $this->assertTrue(get_class($c) === ContainerBoundMethodStub::class);
        $this->assertEquals('default d', $d);
        $this->assertCount(4, $args);

        $app = new Container();
        $app->bind(ContainerCallAbstractStub::class, ContainerBoundMethodStub::class);
        $stub = new ContainerBoundMethodStub();
        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($app, $callee, ['passed a', $stub]);
        $this->assertEquals('passed a', $a);
        $this->assertSame($stub, $b);
        $this->assertTrue(get_class($c) === ContainerBoundMethodStub::class);
        $this->assertEquals('default d', $d);
        $this->assertCount(4, $args);
    }
}

class BoundMethodAccessor extends BoundMethod
{
    public static function getMethodDependencies($container, $callback, array $inputData = [])
    {
        return parent::getMethodDependencies($container, $callback, $inputData);
    }

    public static function isCallableWithAtSign($callback)
    {
        return parent::isCallableWithAtSign($callback);
    }
}

interface ContainerCallAbstractStub
{
    //
}

class ContainerBoundMethodStub implements ContainerCallAbstractStub
{
}

class ContainerBoundMethodStub2
{
}

class BoundMethodAccessorStub
{
    public function injectedAtMiddle($a, ContainerBoundMethodStub $b, $c = 'default c')
    {
    }

    public function injectedAtMiddleTwo($a, ContainerBoundMethodStub $b, $c = 'default c')
    {
    }
}
