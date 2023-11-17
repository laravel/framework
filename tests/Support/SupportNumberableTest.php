<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Numberable;
use PHPUnit\Framework\TestCase;

class SupportNumberableTest extends TestCase
{
    /**
     * @param  int|float  $number
     * @return \Illuminate\Support\Numberable
     */
    protected function numberable($number = 0)
    {
        return new Numberable($number);
    }

    public function testNumberable()
    {
        $this->assertSame(0, $this->numberable()->value());
        $this->assertSame(1, $this->numberable(1)->value());
        $this->assertSame(1.1, $this->numberable(1.1)->value());
    }

    public function testMagicGet()
    {
        $this->assertSame(0, $this->numberable()->value);
        $this->assertSame(1, $this->numberable(1)->value);
        $this->assertSame(1.1, $this->numberable(1.1)->value);
    }

    public function testStringCast()
    {
        $this->assertSame('0', (string) $this->numberable());
        $this->assertSame('1', (string) $this->numberable(1));
        $this->assertSame('1.1', (string) $this->numberable(1.1));
    }

    public function testAdd()
    {
        $number = $this->numberable();

        $this->assertSame(1, $number->add(1)->value());
        $this->assertSame(2, $number->add(1)->value());
        $this->assertSame(-3, $number->add(-5)->value());
    }

    public function testSubtract()
    {
        $number = $this->numberable();

        $this->assertSame(-1, $number->subtract(1)->value());
        $this->assertSame(-2, $number->subtract(1)->value());
        $this->assertSame(3, $number->subtract(-5)->value());
    }

    public function testMultiply()
    {
        $number = $this->numberable(2);

        $this->assertSame(4, $number->multiply(2)->value());
        $this->assertSame(8, $number->multiply(2)->value());
        $this->assertSame(16, $number->multiply(2)->value());

        $number = $this->numberable(2.5);

        $this->assertSame(5.0, $number->multiply(2)->value());
        $this->assertSame(7.5, $number->multiply(1.5)->value());
        $this->assertSame(-1.875, $number->multiply(-0.25)->value());
    }

    public function testDivide()
    {
        $number = $this->numberable(2);

        $this->assertSame(1, $number->divide(2)->value());
        $this->assertSame(0.5, $number->divide(2)->value());
        $this->assertSame(0.25, $number->divide(2)->value());

        $number = $this->numberable(3);

        $this->assertSame(1.5, $number->divide(2)->value());
        $this->assertSame(0.5, $number->divide(3)->value());
        $this->assertSame(-2.0, $number->divide(-0.25)->value());
    }

    public function testModulo()
    {
        $this->assertSame(0, $this->numberable(10)->modulo(2)->value());
        $this->assertSame(1, $this->numberable(10)->modulo(3)->value());
        $this->assertSame(2, $this->numberable(10)->modulo(4)->value());

        // Floats are cast to int before modulo operation
        $this->assertSame(0, $this->numberable(10)->modulo(1.5)->value());
        $this->assertSame(0, $this->numberable(10)->modulo(1.75)->value());
        $this->assertSame(0, $this->numberable(10)->modulo(1.25)->value());
    }

    public function testPow()
    {
        $this->assertSame(1, $this->numberable(10)->pow(0)->value());
        $this->assertSame(10, $this->numberable(10)->pow(1)->value());
        $this->assertSame(100, $this->numberable(10)->pow(2)->value());
        $this->assertSame(1000, $this->numberable(10)->pow(3)->value());
        $this->assertSame(10000, $this->numberable(10)->pow(4)->value());

        $this->assertSame(1.0, $this->numberable(10)->pow(0.0)->value());
        $this->assertSame(10.0, $this->numberable(10)->pow(1.0)->value());
        $this->assertSame(100.0, $this->numberable(10)->pow(2.0)->value());

        $this->assertSame(0.1, $this->numberable(10)->pow(-1)->value());
        $this->assertSame(0.01, $this->numberable(10)->pow(-2)->value());
    }
}
