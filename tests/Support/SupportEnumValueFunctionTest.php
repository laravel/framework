<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Illuminate\Support\enum_value;

include_once 'Enums.php';

class SupportEnumValueFunctionTest extends TestCase
{
    public function test_it_can_handle_enums_value()
    {
        $this->assertSame('A', enum_value(TestEnum::A));

        $this->assertSame(1, enum_value(TestBackedEnum::A));
        $this->assertSame(2, enum_value(TestBackedEnum::B));

        $this->assertSame('A', enum_value(TestStringBackedEnum::A));
        $this->assertSame('B', enum_value(TestStringBackedEnum::B));
    }

    #[DataProvider('scalarDataProvider')]
    public function test_it_can_handle_enum_value($given, $expected)
    {
        $this->assertSame($expected, enum_value($given));
    }

    public static function scalarDataProvider()
    {
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
