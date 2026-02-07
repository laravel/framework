<?php

namespace Illuminate\Tests\Support;

use Brick\Math\RoundingMode;
use DivisionByZeroError;
use Illuminate\Support\Numberable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SupportNumberableTest extends TestCase
{
    public function testMathOperations()
    {
        $result = Numberable::make(42)
            ->add(8)
            ->multiply(2)
            ->subtract(10)
            ->divide(2);

        $this->assertSame(45, $result->value());
        $this->assertTrue($result->isInt());
    }

    public function testRoundSupportsRoundingModes()
    {
        $this->assertSame(2.5, Numberable::make(2.55)->round(1, RoundingMode::Down)->toFloat());
        $this->assertSame(2.6, Numberable::make(2.55)->round(1, RoundingMode::Up)->toFloat());
        $this->assertSame(150, Numberable::make(145)->round(-1, RoundingMode::HalfUp)->toInt());
        $this->assertSame(140, Numberable::make(145)->round(-1, RoundingMode::HalfDown)->toInt());
    }

    public function testRoundingModeCanBeConfiguredWithWither()
    {
        $number = Numberable::make(1.5)->withRoundingMode(RoundingMode::HalfEven);

        $this->assertSame(2, $number->round()->toInt());
        $this->assertSame(2, $number->add(1)->round()->toInt());
    }

    public function testDivisionByZeroThrowsException()
    {
        $this->expectException(DivisionByZeroError::class);

        Numberable::make(10)->divide(0);
    }

    public function testModuloByZeroThrowsException()
    {
        $this->expectException(DivisionByZeroError::class);

        Numberable::make(10)->mod(0);
    }

    public function testFormat()
    {
        $number = Numberable::make(1244.56)->withPrecision(1);

        $this->assertSame('1,244.6', $number->format());
        $this->assertSame('1,244.6', (string) $number);
        $this->assertSame('1 244,56', Numberable::make(1244.56)->format(2, ',', ' '));
    }

    public function testPredicates()
    {
        $this->assertTrue(Numberable::make(10)->isPositive());
        $this->assertTrue(Numberable::make(-10)->isNegative());
        $this->assertTrue(Numberable::make(10.0)->isInt());
        $this->assertTrue(Numberable::make(10.5)->isFloat());
    }

    public function testPairs()
    {
        $this->assertSame(['whole' => 10, 'fraction' => 25], Numberable::make(10.25)->pairs());
        $this->assertSame(['whole' => -10, 'fraction' => 25], Numberable::make(-10.25)->pairs());
        $this->assertSame(['whole' => 10, 'fraction' => 0], Numberable::make(10)->pairs());
    }

    public function testParseMethods()
    {
        $this->assertSame(1234.56, Numberable::parse('1,234.56')->toFloat());
        $this->assertSame(1234.56, Numberable::parse('1.234,56', 'de')->toFloat());
        $this->assertSame(1234.56, Numberable::parse('1.234,56', 'fr_FR')->toFloat());
        $this->assertSame(1234, Numberable::parseInt('1,234.56')->toInt());
        $this->assertSame(1234.56, Numberable::parseFloat('1.234,56', 'de')->toFloat());
    }

    public function testParseThrowsExceptionWhenValueIsInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        Numberable::parse('invalid');
    }

    public function testFormatAs()
    {
        $number = Numberable::make(1234.56)
            ->withLocale('fr')
            ->withCurrency('EUR')
            ->withPrecision(2);

        $this->assertNotSame('', $number->formatAs('currency'));
        $this->assertNotSame('', $number->formatAs('percentage'));
        $this->assertNotSame('', $number->formatAs('abbreviated'));
        $this->assertNotSame('', $number->formatAs('summarized'));
        $this->assertNotSame('', $number->formatAs('humanReadable'));
        $this->assertNotSame('', $number->formatAs('fileSize'));
        $this->assertNotSame('', $number->formatAs('ordinal'));
        $this->assertNotSame('', $number->formatAs('spell'));
        $this->assertNotSame('', $number->formatAs('spellOrdinal'));
    }

    public function testFallbackPercentageRespectsLocaleSymbolPlacementAndSpacing()
    {
        $method = new ReflectionMethod(Numberable::class, 'fallbackPercentage');
        $method->setAccessible(true);

        $number = Numberable::make(12.5);

        $this->assertSame('12.5%', $method->invoke($number, 1, null, 'en'));
        $this->assertSame('12.5 %', $method->invoke($number, 1, null, 'fr'));
        $this->assertSame('%12.5', $method->invoke($number, 1, null, 'tr'));
        $this->assertSame('12.5٪', $method->invoke($number, 1, null, 'fa'));
        $this->assertSame('12.5٪؜', $method->invoke($number, 1, null, 'ar'));
    }

    public function testFormatAsCanBeExtendedWithCustomStyle()
    {
        Numberable::registerFormat('compact', fn ($value) => 'v:'.(int) $value);

        $number = Numberable::make(512)->formatAs('compact');

        $this->assertSame('v:512', $number);
    }

    public function testToStringUsesReadableDefaultRepresentation()
    {
        $number = Numberable::make(1000.125)->withMaxPrecision(3);

        $this->assertSame('1,000.125', (string) $number);
    }
}
