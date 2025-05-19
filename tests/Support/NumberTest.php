<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Number;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NumberTest extends TestCase
{
    public function testRoman()
    {
        $this->assertSame('I', Number::roman(1));
        $this->assertSame('IV', Number::roman(4));
        $this->assertSame('IX', Number::roman(9));
        $this->assertSame('LVIII', Number::roman(58));
        $this->assertSame('MCMXCIV', Number::roman(1994));
        $this->assertSame('MMMCMXCIX', Number::roman(3999));
    }

    public function testRomanWithInvalidLowNumber()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Number must be between 1 and 3999.');
        Number::roman(0);
    }

    public function testRomanWithInvalidHighNumber()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Number must be between 1 and 3999.');
        Number::roman(4000);
    }
}
