<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Num;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SupportNumTest extends TestCase
{
    public function testNumberCanBeAbsolute(): void
    {
        $this->assertSame(10, Num::of(-10)->abs()->toInt());
    }

    public function testNumberCanBeAdded(): void
    {
        $this->assertSame(20, Num::of('10')->add('10')->toInt());
        $this->assertSame(20, Num::of('10')->add(10)->toInt());
        $this->assertSame(20, Num::of(10)->add('10')->toInt());
        $this->assertSame(20, Num::of(10)->add(10)->toInt());
    }

    public function testNumberCanBeCastedToFloat(): void
    {
        $this->assertSame(-10.0, Num::of('-10')->toFloat());
        $this->assertSame(10.0, Num::of('10')->toFloat());
        $this->assertSame(10.0, Num::of(10)->toFloat());
        $this->assertSame(10.10, Num::of('10.10')->toFloat());
        $this->assertSame(10.10, Num::of(10.10)->toFloat());
    }

    public function testNumberCanBeCastedToInt(): void
    {
        $this->assertSame(-10, Num::of('-10')->toInt());
        $this->assertSame(10, Num::of('10')->toInt());
        $this->assertSame(10, Num::of('10.10')->toInt());
        $this->assertSame(10, Num::of(10)->toInt());
        $this->assertSame(10, Num::of(10.10)->toInt());
    }

    public function testNumberCanBeCheckedIfFloat(): void
    {
        $this->assertFalse(Num::of('10.0')->isFloat());
        $this->assertFalse(Num::of(10)->isFloat());
        $this->assertTrue(Num::of(10.0)->isFloat());
    }

    public function testNumberCanBeCheckedIfGreaterThan(): void
    {
        $this->assertFalse(Num::of(10)->gt(100));
        $this->assertFalse(Num::of(10)->gt(10));
        $this->assertTrue(Num::of(10)->gt(5));
    }

    public function testNumberCanBeCheckedIfGreaterThanOrEqual(): void
    {
        $this->assertFalse(Num::of(10)->gte(100));
        $this->assertTrue(Num::of(10)->gte(10));
        $this->assertTrue(Num::of(10)->gte(5));
    }

    public function testNumberCanBeCheckedIfInt(): void
    {
        $this->assertFalse(Num::of('10')->isInt());
        $this->assertFalse(Num::of(10.0)->isInt());
        $this->assertTrue(Num::of(10)->isInt());
    }

    public function testNumberCanBeCheckedIfLessThan(): void
    {
        $this->assertFalse(Num::of(10)->lt(10));
        $this->assertFalse(Num::of(10)->lt(5));
        $this->assertTrue(Num::of(10)->lt(100));
    }

    public function testNumberCanBeCheckedIfLessThanOrEqual(): void
    {
        $this->assertFalse(Num::of(10)->lte(5));
        $this->assertTrue(Num::of(10)->lte(10));
        $this->assertTrue(Num::of(10)->lte(100));
    }

    public function testNumberCanBeCheckedIfNegative(): void
    {
        $this->assertFalse(Num::of(10)->isNegative());
        $this->assertTrue(Num::of(-10)->isNegative());
    }

    public function testNumberCanBeCheckedIfNumeric(): void
    {
        $this->assertTrue(Num::of('10.0')->isNumeric());
        $this->assertTrue(Num::of(10)->isNumeric());
        $this->assertTrue(Num::of(10.0)->isNumeric());
    }

    public function testNumberCanBeCheckedIfPositive(): void
    {
        $this->assertFalse(Num::of(-10)->isPositive());
        $this->assertTrue(Num::of(10)->isPositive());
    }

    public function testNumberCanBeCheckedIfZero(): void
    {
        $this->assertFalse(Num::of(-10)->isZero());
        $this->assertFalse(Num::of(10)->isZero());
        $this->assertTrue(Num::of('0')->isZero());
        $this->assertTrue(Num::of(0)->isZero());
        $this->assertTrue(Num::of(0.0)->isZero());
    }

    public function testNumberCanBeDivided(): void
    {
        $this->assertSame(1, Num::of(10)->divide(10)->toInt());
        $this->assertSame(1.0, Num::of(10)->divide(10)->toFloat());
    }

    public function testNumberCanBeFormatted(): void
    {
        $this->assertSame('10', Num::of('10.0')->format());
        $this->assertSame('10', Num::of(10)->format());
        $this->assertSame('10', Num::of(10.0)->format());
        $this->assertSame('10,00', Num::of('10.0')->format(2, ','));
        $this->assertSame('10,00', Num::of(10)->format(2, ','));
        $this->assertSame('10,00', Num::of(10.0)->format(2, ','));
        $this->assertSame('10.00', Num::of('10.0')->format(2));
        $this->assertSame('10.00', Num::of(10)->format(2));
        $this->assertSame('10.00', Num::of(10.0)->format(2));
        $this->assertSame('1_000,00', Num::of('1000.0')->format(2, ',', '_'));
        $this->assertSame('1_000,00', Num::of(1000)->format(2, ',', '_'));
        $this->assertSame('1_000,00', Num::of(1000.0)->format(2, ',', '_'));
    }

    public function testNumberCanBeLog(): void
    {
        $this->assertSame(2, Num::of('100')->log('10')->toInt());
        $this->assertSame(2, Num::of('100')->log(10)->toInt());
        $this->assertSame(2, Num::of(100)->log('10')->toInt());
        $this->assertSame(2, Num::of(100)->log(10)->toInt());
    }

    public function testNumberCanBeLog10(): void
    {
        $this->assertSame(2, Num::of('100')->log10()->toInt());
        $this->assertSame(2, Num::of('100')->log10()->toInt());
        $this->assertSame(2, Num::of(100)->log10()->toInt());
        $this->assertSame(2, Num::of(100)->log10()->toInt());
    }

    public function testNumberCanBeMultiplied(): void
    {
        $this->assertSame(100, Num::of(10)->multiply(10)->toInt());
        $this->assertSame(100.0, Num::of(10)->multiply(10)->toFloat());
    }

    public function testNumberCanBeSubtracted(): void
    {
        $this->assertSame(0, Num::of('10')->sub('10')->toInt());
        $this->assertSame(0, Num::of('10')->sub(10)->toInt());
        $this->assertSame(0, Num::of(10)->sub('10')->toInt());
        $this->assertSame(0, Num::of(10)->sub(10)->toInt());
    }

    public function testNumberCanExponential(): void
    {
        $this->assertSame(100, Num::of('10')->pow('2')->toInt());
        $this->assertSame(100, Num::of('10')->pow(2)->toInt());
        $this->assertSame(100, Num::of(10)->pow('2')->toInt());
        $this->assertSame(100, Num::of(10)->pow(2)->toInt());
    }

    public function testNumberCanSquareRoot(): void
    {
        $this->assertSame(10, Num::of('100')->sqrt()->toInt());
        $this->assertSame(10, Num::of(100)->sqrt()->toInt());
    }

    public function testNumberThrowsAnInvalidArgumentExceptionWhenNonNumericValuePassed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Num::of('String');
    }
}
