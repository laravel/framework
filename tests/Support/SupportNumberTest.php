<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Number;
use PHPUnit\Framework\TestCase;

class SupportNumberTest extends TestCase
{
    public function testPercentage() {
        $this->assertSame('23.50%', Number::percentage(23.5));
        $this->assertSame('23.50 %', Number::percentage(23.5, ['format' => '%s %s']));
        $this->assertSame('23.501%', Number::percentage(23.501, ['precision' => 3]));
        $this->assertSame('1 305,01 %', Number::percentage(1305.01, ['locale' => 'fr']));
        $this->assertSame('9 988 776,65 %', Number::percentage(9988776.65, ['locale' => 'fr']));
        $this->assertSame('9.988.776,65 %', Number::percentage(9988776.65, ['locale' => 'de']));
        $this->assertSame('9,988,776.65%', Number::percentage(9988776.65, ['locale' => 'en']));
        $this->assertSame('135,00 %', Number::percentage(135.0, ['locale' => 'fr']));
        $this->assertSame('1,305.04%', Number::percentage(1305.04, ['locale' => 'en']));
        $this->assertSame('1,305%', Number::percentage(1305.034, ['precision' => 0]));
        $this->assertSame('1,305.4%', Number::percentage(1305.400, ['strip_insignificant_zeros' => true]));
        $this->assertSame('305%', Number::percentage(305, ['strip_insignificant_zeros' => true]));
        $this->assertSame('306%', Number::percentage(306.00, ['strip_insignificant_zeros' => true]));
        $this->assertSame('307.11%', Number::percentage(307.110, ['precision' => 3, 'strip_insignificant_zeros' => true]));

        $this->expectException(\RuntimeException::class, );
        Number::percentage(23.5, ['locale' => 'es']);
    }
}
