<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Number;
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

    public function testFormat()
    {
        $this->assertSame($this->numberable(10)->format(), Number::format(10));
        $this->assertSame($this->numberable(1.234)->format(2), Number::format(1.234, 2));
        $this->assertSame($this->numberable(1.234)->format(2, 1), Number::format(1.234, 2, 1));
        $this->assertSame($this->numberable(10000)->format(locale: 'de'), Number::format(10000, locale: 'de'));
    }

    public function testPercentage()
    {
        $this->assertSame($this->numberable(10)->percentage(), Number::percentage(10));
        $this->assertSame($this->numberable(1.234)->percentage(2), Number::percentage(1.234, 2));
        $this->assertSame($this->numberable(1.234)->percentage(2, 1), Number::percentage(1.234, 2, 1));
        $this->assertSame($this->numberable(10000)->percentage(locale: 'de'), Number::percentage(10000, locale: 'de'));
    }

    public function testCurrency()
    {
        $this->assertSame($this->numberable(10)->currency(), Number::currency(10));
        $this->assertSame($this->numberable(1.234)->currency(2), Number::currency(1.234, 2));
        $this->assertSame($this->numberable(1.234)->currency(2, 1), Number::currency(1.234, 2, 1));
        $this->assertSame($this->numberable(10000)->currency(locale: 'de'), Number::currency(10000, locale: 'de'));
    }

    public function testFileSize()
    {
        $this->assertSame($this->numberable(10)->fileSize(), Number::fileSize(10));
        $this->assertSame($this->numberable(1.234)->fileSize(2), Number::fileSize(1.234, 2));
        $this->assertSame($this->numberable(1.234)->fileSize(2, 1), Number::fileSize(1.234, 2, 1));
        $this->assertSame($this->numberable(10000)->fileSize(), Number::fileSize(10000));
    }

    public function testForHumans()
    {
        $this->assertSame($this->numberable(10)->forHumans(), Number::forHumans(10));
        $this->assertSame($this->numberable(1.234)->forHumans(2), Number::forHumans(1.234, 2));
        $this->assertSame($this->numberable(1.234)->forHumans(2, 1), Number::forHumans(1.234, 2, 1));
        $this->assertSame($this->numberable(10000)->forHumans(), Number::forHumans(10000));
    }

    public function testTap()
    {
        $this->assertSame(15, $this->numberable(10)->tap(function (Numberable $number) {
            $number->add(5);
        })->value);
    }

    public function testMacro()
    {
        Numberable::macro('addFive', function () {
            return $this->add(5);
        });

        $this->assertSame(15, $this->numberable(10)->addFive()->value);
    }

    public function testWhen()
    {
        $this->assertSame(15, $this->numberable(10)->when(true, function (Numberable $number) {
            return $number->add(5);
        })->value);

        $this->assertSame(10, $this->numberable(10)->when(false, function (Numberable $number) {
            return $number->add(5);
        })->value);

        $this->assertSame(15, $this->numberable(10)->when(true, function (Numberable $number) {
            return $number->add(5);
        }, function (Numberable $number) {
            return $number->add(10);
        })->value);

        $this->assertSame(20, $this->numberable(10)->when(false, function (Numberable $number) {
            return $number->add(5);
        }, function (Numberable $number) {
            return $number->add(10);
        })->value);
    }

    public function testUnless()
    {
        $this->assertSame(15, $this->numberable(10)->unless(false, function (Numberable $number) {
            return $number->add(5);
        })->value);

        $this->assertSame(10, $this->numberable(10)->unless(true, function (Numberable $number) {
            return $number->add(5);
        })->value);

        $this->assertSame(15, $this->numberable(10)->unless(false, function (Numberable $number) {
            return $number->add(5);
        }, function (Numberable $number) {
            return $number->add(10);
        })->value);

        $this->assertSame(20, $this->numberable(10)->unless(true, function (Numberable $number) {
            return $number->add(5);
        }, function (Numberable $number) {
            return $number->add(10);
        })->value);
    }

    public function testFluentOperations()
    {
        $number = $this->numberable(10);

        $this->assertSame(10, (int) (string) $number);
        $this->assertSame(15, (int) (string) $number->add(5));
        $this->assertSame(5, (int) (string) $number->subtract(10));
        $this->assertSame(50, (int) (string) $number->multiply(10));
        $this->assertSame(25, (int) (string) $number->divide(2));
        $this->assertSame(5, (int) (string) $number->modulo(20));
        $this->assertSame(25, (int) (string) $number->pow(2));

        $this->assertSame('21 trillion', $number->subtract(17)->multiply(10)->pow(7)->forHumans());
    }
}
