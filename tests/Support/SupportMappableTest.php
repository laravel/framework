<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Mappable;
use Illuminate\Support\Traits\Tappable;
use PHPUnit\Framework\TestCase;

class SupportMappableTest extends TestCase
{
    public function testMappableClassWithCallback()
    {
        $name = MappableClass::make()->tap(function ($mappable) {
            $mappable->setName('MyName');
        })->map(function ($mappable) {
            return Str::of($mappable->getName())->snake()->toString();
        });

        $this->assertSame('my_name', $name);
    }

    public function testMappableClassWithInvokableClass()
    {
        $name = MappableClass::make()->tap(new class
        {
            public function __invoke($mappable)
            {
                $mappable->setName('MyName');
            }
        })->map(new class
        {
            public function __invoke($mappable)
            {
                return Str::of($mappable->getName())->snake()->toString();
            }
        });

        $this->assertSame('my_name', $name);
    }

    public function testMappableClassWithNoneInvokableClass()
    {
        $this->expectException('Error');

        $name = MappableClass::make()->tap(function ($mappable) {
            $mappable->setName('MyName');
        })->map(new class
        {
            public function getSnakeName($mappable)
            {
                return Str::of($mappable->getName())->snake()->toString();
            }
        })->getName();

        $this->assertSame('my_name', $name);
    }
}


class MappableClass
{
    use Tappable;
    use Mappable;

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
