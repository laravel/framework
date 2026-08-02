<?php

namespace Illuminate\Tests\Support;

use Closure;
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

        $this->assertParameterTypes([null, ExampleParameter::class], function ($one, ?ExampleParameter $two = null) {
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

    public function testItWorksWithUnionTypes()
    {
        $types = ReflectsClosuresClass::reflectFirstAll(function (ExampleParameter $a, $b) {
            //
        });

        $this->assertEquals([
            ExampleParameter::class,
        ], $types);

        $closure = require __DIR__.'/Fixtures/UnionTypesClosure.php';

        $types = ReflectsClosuresClass::reflectFirstAll($closure);

        $this->assertEquals([
            ExampleParameter::class,
            AnotherExampleParameter::class,
        ], $types);
    }

    public function testItWorksWithUnionTypesWithNoTypeHints()
    {
        $this->expectException(RuntimeException::class);

        $types = ReflectsClosuresClass::reflectFirstAll(function ($a, $b) {
            //
        });
    }

    public function testItWorksWithUnionTypesWithNoArguments()
    {
        $this->expectException(RuntimeException::class);

        $types = ReflectsClosuresClass::reflectFirstAll(function () {
            //
        });
    }

    public function testClosureReturnTypesReturnsClassReturnType(): void
    {
        $this->assertSame(
            [ExampleParameter::class],
            ReflectsClosuresClass::reflectReturnTypes(fn (): ExampleParameter => new ExampleParameter)
        );
    }

    public function testClosureReturnTypesExcludesBuiltinReturnType(): void
    {
        $this->assertSame(
            [],
            ReflectsClosuresClass::reflectReturnTypes(fn (): string => 'foo')
        );
    }

    public function testClosureReturnTypesReturnsEmptyArrayWhenNoReturnType(): void
    {
        $this->assertSame(
            [],
            ReflectsClosuresClass::reflectReturnTypes(fn () => 'foo')
        );
    }

    public function testClosureReturnTypesExcludesSelfAndStaticReturnTypes(): void
    {
        $this->assertSame([], ReflectsClosuresClass::reflectReturnTypes(ReflectsClosuresClass::closureWithSelfReturnType()));
        $this->assertSame([], ReflectsClosuresClass::reflectReturnTypes(ReflectsClosuresClass::closureWithStaticReturnType()));
    }

    public function testClosureReturnTypesKeepsOnlyClassTypesFromUnionReturnType(): void
    {
        $this->assertSame(
            [ExampleParameter::class],
            ReflectsClosuresClass::reflectReturnTypes(fn (): ExampleParameter|string => new ExampleParameter)
        );

        $this->assertSame(
            [ExampleParameter::class, AnotherExampleParameter::class],
            ReflectsClosuresClass::reflectReturnTypes(fn (): ExampleParameter|AnotherExampleParameter => new ExampleParameter)
        );
    }

    public function testClosureReturnTypesReturnsEmptyArrayForIntersectionReturnType(): void
    {
        $this->assertSame(
            [],
            ReflectsClosuresClass::reflectReturnTypes(fn (): ReflectsClosuresInterfaceOne&ReflectsClosuresInterfaceTwo => new ReflectsClosuresBothInterfaces)
        );
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

    public static function reflectFirstAll($closure)
    {
        return (new static)->firstClosureParameterTypes($closure);
    }

    public static function reflectReturnTypes($closure)
    {
        return (new static)->closureReturnTypes($closure);
    }

    public static function closureWithSelfReturnType(): Closure
    {
        return function (): self {
            return new self;
        };
    }

    public static function closureWithStaticReturnType(): Closure
    {
        return function (): static {
            return new static;
        };
    }
}

class ExampleParameter
{
    //
}

class AnotherExampleParameter
{
    //
}

interface ReflectsClosuresInterfaceOne
{
    //
}

interface ReflectsClosuresInterfaceTwo
{
    //
}

class ReflectsClosuresBothInterfaces implements ReflectsClosuresInterfaceOne, ReflectsClosuresInterfaceTwo
{
    //
}
