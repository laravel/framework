<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Num;
use PHPUnit\Framework\TestCase;

class SupportNumTest extends TestCase
{
    public function testConversionOfNumberWithPointDecimalSeparatorToFloat()
    {
        $this->assertSame(123.0, Num::float('123', Num::POINT));
        $this->assertSame(1234567.89, Num::float('1,234,567.89', Num::POINT));
        $this->assertSame(1234567.0, Num::float('1,234,567', Num::POINT));
        $this->assertSame(1234567.89, Num::float('1 234 567.89', Num::POINT));
        $this->assertSame(1234567.89, Num::float('123,4567.89', Num::POINT));
        $this->assertSame(1234567.89, Num::float('1\'234\'567.89', Num::POINT));
        $this->assertSame(12345.0, Num::float('12,345', Num::POINT));
        $this->assertSame(123456.0, Num::float('12,3456', Num::POINT));
        $this->assertSame(3.14159265359, Num::float('3.14159265359', Num::POINT));
        $this->assertSame(12.30, Num::float('$12.30', Num::POINT));
        $this->assertSame(0.0, Num::float('text', Num::POINT));
    }

    public function testConversionOfNumberWithPointDecimalSeparatorToInteger()
    {
        $this->assertSame(123, Num::int('123', Num::POINT));
        $this->assertSame(1234567, Num::int('1,234,567.89', Num::POINT));
        $this->assertSame(1234567, Num::int('1,234,567', Num::POINT));
        $this->assertSame(1234567, Num::int('1 234 567.89', Num::POINT));
        $this->assertSame(1234567, Num::int('123,4567.89', Num::POINT));
        $this->assertSame(1234567, Num::int('1\'234\'567.89', Num::POINT));
        $this->assertSame(12345, Num::int('12,345', Num::POINT));
        $this->assertSame(123456, Num::int('12,3456', Num::POINT));
        $this->assertSame(12, Num::int('$12.30', Num::POINT));
        $this->assertSame(0, Num::int('text', Num::POINT));
    }

    public function testConversionOfNumberWithCommaDecimalSeparatorToFloat()
    {
        $this->assertSame(123.0, Num::float('123', Num::COMMA));
        $this->assertSame(1234567.89, Num::float('1 234 567,89'), Num::COMMA);
        $this->assertSame(1234567.89, Num::float('1.234.567,89'), Num::COMMA);
        $this->assertSame(1234567.89, Num::float('1\'234\'567,89'), Num::COMMA);
        $this->assertSame(12.345, Num::float('12,345', Num::COMMA));
        $this->assertSame(3.14159265359, Num::float('3,14159265359', Num::COMMA));
        $this->assertSame(0.0, Num::float('text', Num::COMMA));
    }

    public function testConversionOfNumberWithCommaDecimalSeparatorToInteger()
    {
        $this->assertSame(123, Num::int('123', Num::COMMA));
        $this->assertSame(1234567, Num::int('1 234 567,89'), Num::COMMA);
        $this->assertSame(1234567, Num::int('1.234.567,89'), Num::COMMA);
        $this->assertSame(1234567, Num::int('1\'234\'567,89'), Num::COMMA);
        $this->assertSame(12, Num::int('12,345', Num::COMMA));
        $this->assertSame(0, Num::int('text', Num::COMMA));
    }

    public function testGuessingDecimalSeparatorFromNumberString()
    {
        $this->assertSame(Num::POINT, Num::guessDecimalSeparator('1,234,567.89'));
        $this->assertSame(Num::POINT, Num::guessDecimalSeparator('1,234,567'));
        $this->assertSame(Num::POINT, Num::guessDecimalSeparator('1 234 567.89'));
        $this->assertSame(Num::POINT, Num::guessDecimalSeparator('123,4567.89'));
        $this->assertSame(Num::POINT, Num::guessDecimalSeparator('1\'234\'567.89'));
        $this->assertSame(Num::POINT, Num::guessDecimalSeparator('123'));
        $this->assertSame(Num::POINT, Num::guessDecimalSeparator('text'));
        $this->assertSame(Num::COMMA, Num::guessDecimalSeparator('1.234.567,89'));
        $this->assertSame(Num::COMMA, Num::guessDecimalSeparator('1\'234\'567,89'));
        $this->assertSame(Num::COMMA, Num::guessDecimalSeparator('12,345'));
        $this->assertSame(Num::COMMA, Num::guessDecimalSeparator('12,3456'));
    }
}
