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

    /**
     * @requires PHP 8
     */
    public function testUnionTypeName()
    {
        $method = (new ReflectionClass(C::class))->getMethod('f');

        $this->assertNull(Reflector::getParameterClassName($method->getParameters()[0]));
    }
}

class A
{
}

class B extends A
{
    public function f(parent $x)
    {
    }
}

if (PHP_MAJOR_VERSION >= 8) {
    eval('
namespace Illuminate\Tests\Support;

class C
{
    public function f(A|Model $x)
    {
    }
}'
    );
}
