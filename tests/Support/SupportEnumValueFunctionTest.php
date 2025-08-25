<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Illuminate\Support\enum_value;

include_once 'Enums.php';

class SupportEnumValueFunctionTest extends TestCase
{
    #[DataProvider('scalarDataProvider')]
    public function test_it_can_handle_enum_value($given, $expected)
    {
        $this->assertSame($expected, enum_value($given));
    }

    public function test_it_can_fallback_to_use_default_if_value_is_null()
    {
        $this->assertSame('laravel', enum_value(null, 'laravel'));
        $this->assertSame('laravel', enum_value(null, fn () => 'laravel'));
    }

    public static function scalarDataProvider()
    {
        yield [TestEnum::A, 'A'];
        yield [TestBackedEnum::A, 1];
        yield [TestBackedEnum::B, 2];
        yield [TestStringBackedEnum::A, 'A'];
        yield [TestStringBackedEnum::B, 'B'];
        yield [null, null];
        yield [0, 0];
        yield ['0', '0'];
        yield [false, false];
        yield [1, 1];
        yield ['1', '1'];
        yield [true, true];
        yield [[], []];
        yield ['', ''];
        yield ['laravel', 'laravel'];
        yield [true, true];
        yield [1337, 1337];
        yield [1.0, 1.0];
        yield [$collect = collect(), $collect];
    }
}
