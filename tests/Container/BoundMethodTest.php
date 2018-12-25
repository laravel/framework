<?php

namespace Illuminate\Tests\Container;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Container\BoundMethod;

class BoundMethodAccessor extends BoundMethod
{
    public static function getMethodDependencies($callback, array $inputData = [])
    {
        return parent::getMethodDependencies($callback, $inputData);
    }

    public static function isCallableWithAtSign($callback)
    {
        return parent::isCallableWithAtSign($callback);
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
        BoundMethodAccessor::setContainer(new Container());

        $defaulty = function ($a, $b = 'default b', $c = 'default c') {
        };

        $args = BoundMethodAccessor::getMethodDependencies($defaulty, ['a', 'b', 'c']);
        $this->assertSame(['a', 'b', 'c'], $args);

        $args = BoundMethodAccessor::getMethodDependencies($defaulty, ['a', 'b']);
        $this->assertSame(['a', 'b', 'default c'], $args);

        $args = BoundMethodAccessor::getMethodDependencies($defaulty, ['a', 'b', null]);
        $this->assertSame(['a', 'b', null], $args);

        $args = BoundMethodAccessor::getMethodDependencies($defaulty, ['a', null]);
        $this->assertSame(['a', null, 'default c'], $args);
    }

    public function testEndInjection()
    {
        BoundMethodAccessor::setContainer(new Container());

        $injected = function ($a, $b = 'default b', ContainerBoundMethodStub $c) {
        };

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($injected, ['a']);
        $this->assertEquals('a', $a);
        $this->assertEquals('default b', $b);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($injected, [null, null]);
        $this->assertNull($a);
        $this->assertNull($b);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $c);
        $this->assertArrayNotHasKey(4, $args);

        $args = BoundMethodAccessor::getMethodDependencies($injected, [null, null, null]);
        $this->assertSame([null, null, null], $args);
        $this->assertArrayNotHasKey(4, $args);

        $args = BoundMethodAccessor::getMethodDependencies($injected, ['a', 'b', 'c']);
        $this->assertEquals(['a', 'b', 'c'], $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($injected, [
            'a' => 'passed a',
            'b' => 'value b',
            'junk' => 'junk',
        ]);
        $this->assertEquals('passed a', $a);
        $this->assertEquals('value b', $b);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($injected, [
            'a' => 'passed a',
            'junk' => 'junk',
        ]);
        $this->assertEquals('passed a', $a);
        $this->assertEquals('default b', $b);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $c);
        $this->assertArrayNotHasKey(4, $args);
    }

    public function testExtraNumberOfInputArePassedIntoMethod()
    {
        BoundMethodAccessor::setContainer(new Container());

        $callable = function ($a, $b) {
        };

        $args = BoundMethodAccessor::getMethodDependencies($callable, ['a', 'b', 'c', 'd']);
        $this->assertEquals(['a', 'b', 'c', 'd'], $args);

        $args = BoundMethodAccessor::getMethodDependencies($callable, ['a', 'b', 'c', null]);
        $this->assertEquals(['a', 'b', 'c', null], $args);
    }

    public function testCallingWithNoArgs()
    {
        BoundMethodAccessor::setContainer(new Container());
        $callee = function () {
        };

        $args = BoundMethodAccessor::getMethodDependencies($callee, ['a', 'b', 'c']);
        $this->assertSame(['a', 'b', 'c'], $args);

        $args = BoundMethodAccessor::getMethodDependencies($callee, ['key_a' => 'value_a', 'key_b' => 'value_b']);
        $this->assertSame(['key_a' => 'value_a', 'key_b' => 'value_b'], $args);
    }

    public function testCanInjectAtMiddle()
    {
        BoundMethodAccessor::setContainer(new Container());
        $callee = function ($a, ContainerBoundMethodStub $b, $c = 'default c') {
        };

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($callee, ['a' => 'passed a', 'junk' => 'junk']);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertEquals('default c', $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($callee, ['c' => 'value c', 'junk' => 'junk', 'a' => 'passed a']);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertEquals('value c', $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($callee, ['passed a', 'value c']);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertEquals('value c', $c);
        $this->assertArrayNotHasKey(4, $args);

        $obj = new ContainerBoundMethodStub;
        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($callee, ['passed a', $obj]);
        $this->assertEquals('passed a', $a);
        $this->assertSame($obj, $b);
        $this->assertEquals('default c', $c);
        $this->assertArrayNotHasKey(4, $args);

        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($callee, ['passed a', $obj, 'passed c']);
        $this->assertEquals('passed a', $a);
        $this->assertSame($obj, $b);
        $this->assertEquals('passed c', $c);
        $this->assertArrayNotHasKey(4, $args);

        $stub2 = new ContainerBoundMethodStub2;
        [$a, $b, $c] = $args = BoundMethodAccessor::getMethodDependencies($callee, ['passed a', $stub2]);
        $this->assertEquals('passed a', $a);
        $this->assertInstanceOf(ContainerBoundMethodStub::class, $b);
        $this->assertSame($stub2, $c);
        $this->assertArrayNotHasKey(4, $args);
    }

    public function testIsCallableWithAtSign()
    {
        $this->assertTrue(BoundMethodAccessor::isCallableWithAtSign('@'));
        $this->assertTrue(BoundMethodAccessor::isCallableWithAtSign('a@'));
        $this->assertTrue(BoundMethodAccessor::isCallableWithAtSign('@a'));
        $this->assertTrue(BoundMethodAccessor::isCallableWithAtSign('a@a'));
        $this->assertTrue(BoundMethodAccessor::isCallableWithAtSign('1@'));
        $this->assertTrue(BoundMethodAccessor::isCallableWithAtSign('@1'));
        $this->assertTrue(BoundMethodAccessor::isCallableWithAtSign('1@1'));

        $this->assertFalse(BoundMethodAccessor::isCallableWithAtSign('I_HaveNoAtSign'));
        $this->assertFalse(BoundMethodAccessor::isCallableWithAtSign(''));
        $this->assertFalse(BoundMethodAccessor::isCallableWithAtSign([]));
        $this->assertFalse(BoundMethodAccessor::isCallableWithAtSign(null));
    }
}
