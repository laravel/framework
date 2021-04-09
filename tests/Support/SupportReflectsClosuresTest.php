<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SupportReflectsClosuresTest extends TestCase
{
    public function testReflectsClosures()
    {
        $this->assertParameterTypes([ExampleParameter::class], function (ExampleParameter $one) {
            // assert the Closure isn't actually executed
            throw new RuntimeException;
        });

        $this->assertParameterTypes([], function () {
            //
        });

        $this->assertParameterTypes([null], function ($one) {
            //
        });

        $this->assertParameterTypes([null, ExampleParameter::class], function ($one, ExampleParameter $two = null) {
            //
        });

        $this->assertParameterTypes([null, ExampleParameter::class], function (string $one, ?ExampleParameter $two) {
            //
        });

        // Because the parameter is variadic, the closure will always receive an array.
        $this->assertParameterTypes([null], function (ExampleParameter ...$vars) {
            //
        });
    }

    public function testItReturnsTheFirstParameterType()
    {
        $type = ReflectsClosuresClass::reflectFirst(function (ExampleParameter $a) {
            //
        });

        $this->assertInstanceOf($type, new ExampleParameter);
    }

    public function testItThrowsWhenNoParameters()
    {
        $this->expectException(RuntimeException::class);

        ReflectsClosuresClass::reflectFirst(function () {
            //
        });
    }

    public function testItThrowsWhenNoFirstParameterType()
    {
        $this->expectException(RuntimeException::class);

        ReflectsClosuresClass::reflectFirst(function ($a, ExampleParameter $b) {
            //
        });
    }

    private function assertParameterTypes($expected, $closure)
    {
        $types = ReflectsClosuresClass::reflect($closure);

        $this->assertSame($expected, $types);
    }
}

class ReflectsClosuresClass
{
    use ReflectsClosures;

    public static function reflect($closure)
    {
        return array_values((new static)->closureParameterTypes($closure));
    }

    public static function reflectFirst($closure)
    {
        return (new static)->firstClosureParameterType($closure);
    }
}

class ExampleParameter
{
    //
}
