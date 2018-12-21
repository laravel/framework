<?php

namespace Illuminate\Tests\Container;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Container\BoundMethod;

class BoundMethodAccessor extends BoundMethod
{
    public static function getMethodDependencies($container, $callback, array $inputData = [])
    {
        return parent::getMethodDependencies($container, $callback, $inputData);
    }
}

class ContainerBoundMethodStub
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
}

class BoundMethodTest extends TestCase
{
    public function testBoundMethodAccessor()
    {
        $container = new Container();

        $defaulty = function ($a, $b = 'default b', $c = 'default c') {
        };

        $args = BoundMethodAccessor::getMethodDependencies($container, $defaulty, ['a', 'b', 'c']);
        $this->assertSame(['a', 'b', 'c'], $args);

        $args = BoundMethodAccessor::getMethodDependencies($container, $defaulty, ['a', 'b']);
        $this->assertSame(['a', 'b', 'default c'], $args);

        $args = BoundMethodAccessor::getMethodDependencies($container, $defaulty, ['a', 'b', null]);
        $this->assertSame(['a', 'b', null], $args);

        $args = BoundMethodAccessor::getMethodDependencies($container, $defaulty, ['a', null]);
        $this->assertSame(['a', null, 'default c'], $args);
    }

    public function testEndInjection()
    {
        $container = new Container();

        $injected = function ($a, $b = 'default b', ContainerBoundMethodStub $c) {
        };

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($container, $injected, ['a']);
        $this->assertEquals('a', $a);
        $this->assertEquals('default b', $b);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($container, $injected, [null, null]);
        $this->assertNull($a);
        $this->assertNull($b);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($container, $injected, [null, null]);
        $this->assertNull($a);
        $this->assertNull($b);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($container, $injected, [
            'a' => 'passed a',
            'b' => 'value b',
            'junk' => 'junk',
        ]);
        $this->assertEquals('passed a', $a);
        $this->assertEquals('value b', $b);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($container, $injected, [
            'a' => 'passed a',
            'junk' => 'junk',
        ]);
        $this->assertEquals('passed a', $a);
        $this->assertEquals('default b', $b);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $c);
        $this->assertArrayNotHasKey(4, $args);
    }

    public function testCallingWithNoArgs()
    {
        $container = new Container();
        $callee = function () {
        };

        $args = BoundMethodAccessor::getMethodDependencies($container, $callee, ['a', 'b', 'c']);
        $this->assertSame(['a', 'b', 'c'], $args);

        $args = BoundMethodAccessor::getMethodDependencies($container, $callee, ['key_a' => 'value_a', 'key_b' => 'value_b']);
        $this->assertSame(['key_a' => 'value_a', 'key_b' => 'value_b'], $args);
    }

    public function testCanInjectAtMiddle()
    {
        $container = new Container();
        $callee = function ($a, ContainerBoundMethodStub $b, $c = 'default c') {
        };

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($container, $callee, ['a' => 'passed a', 'junk' => 'junk']);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertEquals('default c', $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($container, $callee, ['c' => 'value c', 'junk' => 'junk', 'a' => 'passed a']);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertEquals('value c', $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($container, $callee, ['passed a', 'value c']);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertEquals('value c', $c);
        $this->assertArrayNotHasKey(4, $args);

        $obj = new ContainerBoundMethodStub;
        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($container, $callee, ['passed a', $obj]);
        $this->assertEquals('passed a', $a);
        $this->assertSame($obj, $b);
        $this->assertEquals('default c', $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($container, $callee, ['passed a', $obj, 'passed c']);
        $this->assertEquals('passed a', $a);
        $this->assertSame($obj, $b);
        $this->assertEquals('passed c', $c);
        $this->assertArrayNotHasKey(4, $args);

        $stub2 = new ContainerBoundMethodStub2;
        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($container, $callee, ['passed a', $stub2]);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertSame($stub2, $c);
        $this->assertArrayNotHasKey(4, $args);
    }
}
