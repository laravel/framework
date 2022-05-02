<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Reflector;
use Illuminate\Support\Testing\Fakes\BusFake;
use Illuminate\Support\Testing\Fakes\MailFake;
use Illuminate\Support\Testing\Fakes\PendingMailFake;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SupportReflectorTest extends TestCase
{
    public function testGetClassName()
    {
        $method = (new ReflectionClass(PendingMailFake::class))->getMethod('send');

        $this->assertSame(Mailable::class, Reflector::getParameterClassName($method->getParameters()[0]));
    }

    public function testEmptyClassName()
    {
        $method = (new ReflectionClass(MailFake::class))->getMethod('assertSent');

        $this->assertNull(Reflector::getParameterClassName($method->getParameters()[0]));
    }

    public function testStringTypeName()
    {
        $method = (new ReflectionClass(BusFake::class))->getMethod('dispatchedAfterResponse');

        $this->assertNull(Reflector::getParameterClassName($method->getParameters()[0]));
    }

    public function testSelfClassName()
    {
        $method = (new ReflectionClass(Model::class))->getMethod('newPivot');

        $this->assertSame(Model::class, Reflector::getParameterClassName($method->getParameters()[0]));
    }

    public function testParentClassName()
    {
        $method = (new ReflectionClass(B::class))->getMethod('f');

        $this->assertSame(A::class, Reflector::getParameterClassName($method->getParameters()[0]));
    }

    public function testParameterSubclassOfInterface()
    {
        $method = (new ReflectionClass(TestClassWithInterfaceSubclassParameter::class))->getMethod('f');

        $this->assertTrue(Reflector::isParameterSubclassOf($method->getParameters()[0], IA::class));
    }

    public function testUnionTypeName()
    {
        $method = (new ReflectionClass(C::class))->getMethod('f');

        $this->assertNull(Reflector::getParameterClassName($method->getParameters()[0]));
    }

    public function testIsCallable()
    {
        $this->assertTrue(Reflector::isCallable(function () {
        }));
        $this->assertTrue(Reflector::isCallable([B::class, 'f']));
        $this->assertFalse(Reflector::isCallable([TestClassWithCall::class, 'f']));
        $this->assertTrue(Reflector::isCallable([new TestClassWithCall, 'f']));
        $this->assertTrue(Reflector::isCallable([TestClassWithCallStatic::class, 'f']));
        $this->assertFalse(Reflector::isCallable([new TestClassWithCallStatic, 'f']));
        $this->assertFalse(Reflector::isCallable([new TestClassWithCallStatic]));
        $this->assertFalse(Reflector::isCallable(['TotallyMissingClass', 'foo']));
        $this->assertTrue(Reflector::isCallable(['TotallyMissingClass', 'foo'], true));
    }
}

class A
{
}

class B extends A
{
    public function f(parent $x)
    {
        //
    }
}

class C
{
    public function f(A|Model $x)
    {
        //
    }
}

class TestClassWithCall
{
    public function __call($method, $parameters)
    {
        //
    }
}

class TestClassWithCallStatic
{
    public static function __callStatic($method, $parameters)
    {
        //
    }
}

interface IA
{
}

interface IB extends IA
{
}

class TestClassWithInterfaceSubclassParameter
{
    public function f(IB $x)
    {
        //
    }
}
