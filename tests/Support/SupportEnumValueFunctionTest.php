<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Illuminate\Support\scalar_value;

include_once 'Enums.php';

class SupportEnumValueFunctionTest extends TestCase
{
    public function test_it_can_handle_enums_value()
    {
        $this->assertSame('A', scalar_value(TestEnum::A));

        $this->assertSame(1, scalar_value(TestBackedEnum::A));
        $this->assertSame(2, scalar_value(TestBackedEnum::B));

        $this->assertSame('A', scalar_value(TestStringBackedEnum::A));
        $this->assertSame('B', scalar_value(TestStringBackedEnum::B));
    }

    #[DataProvider('scalarDataProvider')]
    public function test_it_can_handle_scalar_value($given, $expected)
    {
        $this->assertSame($expected, scalar_value($given));
    }

    public static function scalarDataProvider()
    {
        yield ['', ''];
        yield ['laravel', 'laravel'];
        yield [true, true];
        yield [1337, 1337];
        yield [1.0, 1.0];
    }
}
