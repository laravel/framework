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

    public function testGetAttributes()
    {
        require_once __DIR__.'/Fixtures/ClassesWithAttributes.php';

        $this->assertSame([], Reflector::getAttributes(Fixtures\ChildClass::class, Fixtures\UnusedAttr::class)->toArray());

        $this->assertSame(
            [Fixtures\ChildClass::class => [], Fixtures\ParentClass::class => []],
            Reflector::getAttributes(Fixtures\ChildClass::class, Fixtures\UnusedAttr::class, true)->toArray()
        );

        $this->assertSame(
            ['quick', 'brown', 'fox'],
            Reflector::getAttributes(Fixtures\ChildClass::class, Fixtures\StrAttr::class)->map->string->all()
        );

        $this->assertSame(
            ['quick', 'brown', 'fox', 'lazy', 'dog'],
            Reflector::getAttributes(Fixtures\ChildClass::class, Fixtures\StrAttr::class, true)->flatten()->map->string->all()
        );

        $this->assertSame(7, Reflector::getAttributes(Fixtures\ChildClass::class, Fixtures\NumAttr::class)->sum->number);
        $this->assertSame(12, Reflector::getAttributes(Fixtures\ChildClass::class, Fixtures\NumAttr::class, true)->flatten()->sum->number);
        $this->assertSame(5, Reflector::getAttributes(Fixtures\ParentClass::class, Fixtures\NumAttr::class)->sum->number);
        $this->assertSame(5, Reflector::getAttributes(Fixtures\ParentClass::class, Fixtures\NumAttr::class, true)->flatten()->sum->number);

        $this->assertSame(
            [Fixtures\ChildClass::class, Fixtures\ParentClass::class],
            Reflector::getAttributes(Fixtures\ChildClass::class, Fixtures\StrAttr::class, true)->keys()->all()
        );

        $this->assertContainsOnlyInstancesOf(
            Fixtures\StrAttr::class,
            Reflector::getAttributes(Fixtures\ChildClass::class, Fixtures\StrAttr::class)->all()
        );

        $this->assertContainsOnlyInstancesOf(
            Fixtures\StrAttr::class,
            Reflector::getAttributes(Fixtures\ChildClass::class, Fixtures\StrAttr::class, true)->flatten()->all()
        );
    }

    public function testGetAttribute()
    {
        require_once __DIR__.'/Fixtures/ClassesWithAttributes.php';

        $this->assertNull(Reflector::getAttribute(Fixtures\ChildClass::class, Fixtures\UnusedAttr::class));
        $this->assertNull(Reflector::getAttribute(Fixtures\ChildClass::class, Fixtures\UnusedAttr::class, true));
        $this->assertNull(Reflector::getAttribute(Fixtures\ChildClass::class, Fixtures\ParentOnlyAttr::class));
        $this->assertInstanceOf(Fixtures\ParentOnlyAttr::class, Reflector::getAttribute(Fixtures\ChildClass::class, Fixtures\ParentOnlyAttr::class, true));
        $this->assertInstanceOf(Fixtures\StrAttr::class, Reflector::getAttribute(Fixtures\ChildClass::class, Fixtures\StrAttr::class));
        $this->assertInstanceOf(Fixtures\StrAttr::class, Reflector::getAttribute(Fixtures\ChildClass::class, Fixtures\StrAttr::class, true));
        $this->assertSame('quick', Reflector::getAttribute(Fixtures\ChildClass::class, Fixtures\StrAttr::class)->string);
        $this->assertSame('quick', Reflector::getAttribute(Fixtures\ChildClass::class, Fixtures\StrAttr::class, true)->string);
        $this->assertSame('lazy', Reflector::getAttribute(Fixtures\ParentClass::class, Fixtures\StrAttr::class)->string);
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
