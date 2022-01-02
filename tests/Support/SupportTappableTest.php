<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Traits\Tappable;
use PHPUnit\Framework\TestCase;

class SupportTappableTest extends TestCase
{
    public function testTappableClassWithCallback()
    {
        $name = TappableClass::make()->tap(function ($tappable) {
            $tappable->setName('MyName');
        })->getName();

        $this->assertSame('MyName', $name);
    }

    public function testTappableClassWithInvokableClass()
    {
        $name = TappableClass::make()->tap(new class
        {
            public function __invoke($tappable)
            {
                $tappable->setName('MyName');
            }
        })->getName();

        $this->assertSame('MyName', $name);
    }

    public function testTappableClassWithNoneInvokableClass()
    {
        $this->expectException('Error');

        $name = TappableClass::make()->tap(new class
        {
            public function setName($tappable)
            {
                $tappable->setName('MyName');
            }
        })->getName();

        $this->assertSame('MyName', $name);
    }

    public function testTappableClassWithoutCallback()
    {
        $name = TappableClass::make()->tap()->setName('MyName')->getName();

        $this->assertSame('MyName', $name);
    }
}

class TappableClass
{
    use Tappable;

    private $name;

    public static function make()
    {
        return new static;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
