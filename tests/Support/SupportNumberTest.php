<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Number;
use Illuminate\Support\Numeral;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

class SupportNumberTest extends TestCase
{
    public function testReturnNumeral()
    {
        $this->assertInstanceOf(Numeral::class, Number::of(1));
    }

    public function testIsNumeric()
    {
        $this->assertTrue(Number::isNumeric(1));
        $this->assertTrue(Number::isNumeric(1.0));
        $this->assertTrue(Number::isNumeric('1'));
        $this->assertTrue(Number::isNumeric('1.0'));
        $this->assertTrue(Number::isNumeric('1.0e10'));
        $this->assertTrue(Number::isNumeric('1.0e-10'));

        $this->assertFalse(Number::isNumeric('Not a number'));
        $this->assertFalse(Number::isNumeric(new \stdClass()));
        $this->assertFalse(Number::isNumeric([]));
        $this->assertFalse(Number::isNumeric(null));
        $this->assertFalse(Number::isNumeric(true));
        $this->assertFalse(Number::isNumeric(false));
    }

    public function testIsEven()
    {
        foreach (range(0, 100) as $number) {
            if ($number % 2 === 0) {
                $this->assertTrue(Number::isEven($number));
            } else {
                $this->assertFalse(Number::isEven($number));
            }
        }
    }

    public function testIsOdd()
    {
        foreach (range(0, 100) as $number) {
            if ($number % 2 !== 0) {
                $this->assertTrue(Number::isOdd($number));
            } else {
                $this->assertFalse(Number::isOdd($number));
            }
        }
    }

    public function testIsFloat()
    {
        $this->assertTrue(Number::isFloat(1.0));
        $this->assertTrue(Number::isFloat(1.1));
        $this->assertTrue(Number::isFloat(1.0e10));
        $this->assertTrue(Number::isFloat(1.0e-10));

        $this->assertFalse(Number::isFloat(1));
        $this->assertFalse(Number::isFloat('1.0'));
        $this->assertFalse(Number::isFloat('Not a number'));
    }

    public function testIsInt()
    {
        $this->assertTrue(Number::isInt(1));
        $this->assertTrue(Number::isInt(0));
        $this->assertTrue(Number::isInt(-1));

        $this->assertFalse(Number::isInt(1.0));
        $this->assertFalse(Number::isInt(1.1));
        $this->assertFalse(Number::isInt(1.0e10));
        $this->assertFalse(Number::isInt(1.0e-10));
        $this->assertFalse(Number::isInt('1'));
        $this->assertFalse(Number::isInt('Not a number'));
    }

    public function testIsPositive()
    {
        $this->assertTrue(Number::isPositive(1));
        $this->assertTrue(Number::isPositive(1.0));
        $this->assertTrue(Number::isPositive(1.1));
        $this->assertTrue(Number::isPositive(1.0e10));
        $this->assertTrue(Number::isPositive(1.0e-10));

        $this->assertFalse(Number::isPositive(0));
        $this->assertFalse(Number::isPositive(-1));
        $this->assertFalse(Number::isPositive(-1.0));
        $this->assertFalse(Number::isPositive(-1.1));
        $this->assertFalse(Number::isPositive(-1.0e10));
        $this->assertFalse(Number::isPositive(-1.0e-10));
    }

    public function testIsPositiveInt()
    {
        $this->assertTrue(Number::isPositiveInt(1));

        $this->assertFalse(Number::isPositiveInt(1.0));
        $this->assertFalse(Number::isPositiveInt(1.1));
        $this->assertFalse(Number::isPositiveInt(1.0e10));
        $this->assertFalse(Number::isPositiveInt(1.0e-10));
        $this->assertFalse(Number::isPositiveInt(0));
        $this->assertFalse(Number::isPositiveInt(-1));
        $this->assertFalse(Number::isPositiveInt(-1.0));
        $this->assertFalse(Number::isPositiveInt(-1.1));
        $this->assertFalse(Number::isPositiveInt(-1.0e10));
        $this->assertFalse(Number::isPositiveInt(-1.0e-10));
    }

    public function testIsPositiveFloat()
    {
        $this->assertTrue(Number::isPositiveFloat(1));
        $this->assertTrue(Number::isPositiveFloat(1.0));
        $this->assertTrue(Number::isPositiveFloat(1.1));
        $this->assertTrue(Number::isPositiveFloat(1.0e10));
        $this->assertTrue(Number::isPositiveFloat(1.0e-10));

        $this->assertFalse(Number::isPositiveFloat(0));
        $this->assertFalse(Number::isPositiveFloat(-1));
        $this->assertFalse(Number::isPositiveFloat(-1.0));
        $this->assertFalse(Number::isPositiveFloat(-1.1));
        $this->assertFalse(Number::isPositiveFloat(-1.0e10));
        $this->assertFalse(Number::isPositiveFloat(-1.0e-10));
    }

    public function isNegative()
    {
        $this->assertTrue(Number::isNegative(-1));

        $this->assertFalse(Number::isNegative(1));
        $this->assertFalse(Number::isNegative(1.0));
        $this->assertFalse(Number::isNegative(1.1));
        $this->assertFalse(Number::isNegative(1.0e10));
        $this->assertFalse(Number::isNegative(1.0e-10));
        $this->assertFalse(Number::isNegative(0));
    }

    public function testIsNegativeInt()
    {
        $this->assertTrue(Number::isNegativeInt(-1));

        $this->assertFalse(Number::isNegativeInt(1));
        $this->assertFalse(Number::isNegativeInt(1.0));
        $this->assertFalse(Number::isNegativeInt(1.1));
        $this->assertFalse(Number::isNegativeInt(1.0e10));
        $this->assertFalse(Number::isNegativeInt(1.0e-10));
        $this->assertFalse(Number::isNegativeInt(0));
        $this->assertFalse(Number::isNegativeInt(-1.0));
        $this->assertFalse(Number::isNegativeInt(-1.1));
        $this->assertFalse(Number::isNegativeInt(-1.0e10));
        $this->assertFalse(Number::isNegativeInt(-1.0e-10));
    }

    public function testIsNegativeFloat()
    {
        $this->assertTrue(Number::isNegativeFloat(-1));
        $this->assertTrue(Number::isNegativeFloat(-1.0));
        $this->assertTrue(Number::isNegativeFloat(-1.1));
        $this->assertTrue(Number::isNegativeFloat(-1.0e10));
        $this->assertTrue(Number::isNegativeFloat(-1.0e-10));

        $this->assertFalse(Number::isNegativeFloat(1));
        $this->assertFalse(Number::isNegativeFloat(1.0));
        $this->assertFalse(Number::isNegativeFloat(1.1));
        $this->assertFalse(Number::isNegativeFloat(1.0e10));
        $this->assertFalse(Number::isNegativeFloat(1.0e-10));
        $this->assertFalse(Number::isNegativeFloat(0));
    }

    public function testIsZero()
    {
        $this->assertTrue(Number::isZero(0));
        $this->assertTrue(Number::isZero(0.0));

        $this->assertFalse(Number::isZero(1));
        $this->assertFalse(Number::isZero(1.0));
        $this->assertFalse(Number::isZero(-1));
        $this->assertFalse(Number::isZero(-1.0));
    }

    #[RequiresPhpExtension('intl')]
    public function testFormat()
    {
        $this->assertSame('0', Number::format(0));
        $this->assertSame('0', Number::format(0.0));
        $this->assertSame('0', Number::format(0.00));
        $this->assertSame('1', Number::format(1));
        $this->assertSame('10', Number::format(10));
        $this->assertSame('25', Number::format(25));
        $this->assertSame('100', Number::format(100));
        $this->assertSame('100,000', Number::format(100000));
        $this->assertSame('100,000.00', Number::format(100000, precision: 2));
        $this->assertSame('100,000.12', Number::format(100000.123, precision: 2));
        $this->assertSame('100,000.123', Number::format(100000.1234, maxPrecision: 3));
        $this->assertSame('100,000.124', Number::format(100000.1236, maxPrecision: 3));
        $this->assertSame('123,456,789', Number::format(123456789));

        $this->assertSame('-1', Number::format(-1));
        $this->assertSame('-10', Number::format(-10));
        $this->assertSame('-25', Number::format(-25));

        $this->assertSame('0.2', Number::format(0.2));
        $this->assertSame('0.20', Number::format(0.2, precision: 2));
        $this->assertSame('0.123', Number::format(0.1234, maxPrecision: 3));
        $this->assertSame('1.23', Number::format(1.23));
        $this->assertSame('-1.23', Number::format(-1.23));
        $this->assertSame('123.456', Number::format(123.456));

        $this->assertSame('∞', Number::format(INF));
        $this->assertSame('NaN', Number::format(NAN));
    }

    #[RequiresPhpExtension('intl')]
    public function testFormatWithDifferentLocale()
    {
        $this->assertSame('123,456,789', Number::format(123456789, locale: 'en'));
        $this->assertSame('123.456.789', Number::format(123456789, locale: 'de'));
        $this->assertSame('123 456 789', Number::format(123456789, locale: 'fr'));
        $this->assertSame('123 456 789', Number::format(123456789, locale: 'ru'));
        $this->assertSame('123 456 789', Number::format(123456789, locale: 'sv'));
    }

    #[RequiresPhpExtension('intl')]
    public function testFormatWithAppLocale()
    {
        $this->assertSame('123,456,789', Number::format(123456789));

        Number::useLocale('de');

        $this->assertSame('123.456.789', Number::format(123456789));

        Number::useLocale('en');
    }

    public function testSpellout()
    {
        $this->assertSame('ten', Number::spell(10));
        $this->assertSame('one point two', Number::spell(1.2));
    }

    #[RequiresPhpExtension('intl')]
    public function testSpelloutWithLocale()
    {
        $this->assertSame('trois', Number::spell(3, 'fr'));
    }

    #[RequiresPhpExtension('intl')]
    public function testSpelloutWithThreshold()
    {
        $this->assertSame('9', Number::spell(9, after: 10));
        $this->assertSame('10', Number::spell(10, after: 10));
        $this->assertSame('eleven', Number::spell(11, after: 10));

        $this->assertSame('nine', Number::spell(9, until: 10));
        $this->assertSame('10', Number::spell(10, until: 10));
        $this->assertSame('11', Number::spell(11, until: 10));

        $this->assertSame('ten thousand', Number::spell(10000, until: 50000));
        $this->assertSame('100,000', Number::spell(100000, until: 50000));
    }

    public function testOrdinal()
    {
        $this->assertSame('1st', Number::ordinal(1));
        $this->assertSame('2nd', Number::ordinal(2));
        $this->assertSame('3rd', Number::ordinal(3));
    }

    #[RequiresPhpExtension('intl')]
    public function testToPercent()
    {
        $this->assertSame('0%', Number::percentage(0, precision: 0));
        $this->assertSame('0%', Number::percentage(0));
        $this->assertSame('1%', Number::percentage(1));
        $this->assertSame('10.00%', Number::percentage(10, precision: 2));
        $this->assertSame('100%', Number::percentage(100));
        $this->assertSame('100.00%', Number::percentage(100, precision: 2));
        $this->assertSame('100.123%', Number::percentage(100.1234, maxPrecision: 3));

        $this->assertSame('300%', Number::percentage(300));
        $this->assertSame('1,000%', Number::percentage(1000));

        $this->assertSame('2%', Number::percentage(1.75));
        $this->assertSame('1.75%', Number::percentage(1.75, precision: 2));
        $this->assertSame('1.750%', Number::percentage(1.75, precision: 3));
        $this->assertSame('0%', Number::percentage(0.12345));
        $this->assertSame('0.00%', Number::percentage(0, precision: 2));
        $this->assertSame('0.12%', Number::percentage(0.12345, precision: 2));
        $this->assertSame('0.1235%', Number::percentage(0.12345, precision: 4));
    }

    #[RequiresPhpExtension('intl')]
    public function testToCurrency()
    {
        $this->assertSame('$0.00', Number::currency(0));
        $this->assertSame('$1.00', Number::currency(1));
        $this->assertSame('$10.00', Number::currency(10));

        $this->assertSame('€0.00', Number::currency(0, 'EUR'));
        $this->assertSame('€1.00', Number::currency(1, 'EUR'));
        $this->assertSame('€10.00', Number::currency(10, 'EUR'));

        $this->assertSame('-$5.00', Number::currency(-5));
        $this->assertSame('$5.00', Number::currency(5.00));
        $this->assertSame('$5.32', Number::currency(5.325));
    }

    #[RequiresPhpExtension('intl')]
    public function testToCurrencyWithDifferentLocale()
    {
        $this->assertSame('1,00 €', Number::currency(1, 'EUR', 'de'));
        $this->assertSame('1,00 $', Number::currency(1, 'USD', 'de'));
        $this->assertSame('1,00 £', Number::currency(1, 'GBP', 'de'));

        $this->assertSame('123.456.789,12 $', Number::currency(123456789.12345, 'USD', 'de'));
        $this->assertSame('123.456.789,12 €', Number::currency(123456789.12345, 'EUR', 'de'));
        $this->assertSame('1 234,56 $US', Number::currency(1234.56, 'USD', 'fr'));
    }

    public function testBytesToHuman()
    {
        $this->assertSame('0 B', Number::fileSize(0));
        $this->assertSame('0.00 B', Number::fileSize(0, precision: 2));
        $this->assertSame('1 B', Number::fileSize(1));
        $this->assertSame('1 KB', Number::fileSize(1024));
        $this->assertSame('2 KB', Number::fileSize(2048));
        $this->assertSame('2.00 KB', Number::fileSize(2048, precision: 2));
        $this->assertSame('1.23 KB', Number::fileSize(1264, precision: 2));
        $this->assertSame('1.234 KB', Number::fileSize(1264.12345, maxPrecision: 3));
        $this->assertSame('1.234 KB', Number::fileSize(1264, 3));
        $this->assertSame('5 GB', Number::fileSize(1024 * 1024 * 1024 * 5));
        $this->assertSame('10 TB', Number::fileSize((1024 ** 4) * 10));
        $this->assertSame('10 PB', Number::fileSize((1024 ** 5) * 10));
        $this->assertSame('1 ZB', Number::fileSize(1024 ** 7));
        $this->assertSame('1 YB', Number::fileSize(1024 ** 8));
        $this->assertSame('1,024 YB', Number::fileSize(1024 ** 9));
    }

    public function testClamp()
    {
        $this->assertSame(2, Number::clamp(1, 2, 3));
        $this->assertSame(3, Number::clamp(5, 2, 3));
        $this->assertSame(5, Number::clamp(5, 1, 10));
        $this->assertSame(4.5, Number::clamp(4.5, 1, 10));
        $this->assertSame(1, Number::clamp(-10, 1, 5));
    }

    public function testToHuman()
    {
        $this->assertSame('1', Number::forHumans(1));
        $this->assertSame('1.00', Number::forHumans(1, precision: 2));
        $this->assertSame('10', Number::forHumans(10));
        $this->assertSame('100', Number::forHumans(100));
        $this->assertSame('1 thousand', Number::forHumans(1000));
        $this->assertSame('1.00 thousand', Number::forHumans(1000, precision: 2));
        $this->assertSame('1 thousand', Number::forHumans(1000, maxPrecision: 2));
        $this->assertSame('1 thousand', Number::forHumans(1230));
        $this->assertSame('1.2 thousand', Number::forHumans(1230, maxPrecision: 1));
        $this->assertSame('1 million', Number::forHumans(1000000));
        $this->assertSame('1 billion', Number::forHumans(1000000000));
        $this->assertSame('1 trillion', Number::forHumans(1000000000000));
        $this->assertSame('1 quadrillion', Number::forHumans(1000000000000000));
        $this->assertSame('1 thousand quadrillion', Number::forHumans(1000000000000000000));

        $this->assertSame('123', Number::forHumans(123));
        $this->assertSame('1 thousand', Number::forHumans(1234));
        $this->assertSame('1.23 thousand', Number::forHumans(1234, precision: 2));
        $this->assertSame('12 thousand', Number::forHumans(12345));
        $this->assertSame('1 million', Number::forHumans(1234567));
        $this->assertSame('1 billion', Number::forHumans(1234567890));
        $this->assertSame('1 trillion', Number::forHumans(1234567890123));
        $this->assertSame('1.23 trillion', Number::forHumans(1234567890123, precision: 2));
        $this->assertSame('1 quadrillion', Number::forHumans(1234567890123456));
        $this->assertSame('1.23 thousand quadrillion', Number::forHumans(1234567890123456789, precision: 2));
        $this->assertSame('490 thousand', Number::forHumans(489939));
        $this->assertSame('489.9390 thousand', Number::forHumans(489939, precision: 4));
        $this->assertSame('500.00000 million', Number::forHumans(500000000, precision: 5));

        $this->assertSame('1 million quadrillion', Number::forHumans(1000000000000000000000));
        $this->assertSame('1 billion quadrillion', Number::forHumans(1000000000000000000000000));
        $this->assertSame('1 trillion quadrillion', Number::forHumans(1000000000000000000000000000));
        $this->assertSame('1 quadrillion quadrillion', Number::forHumans(1000000000000000000000000000000));
        $this->assertSame('1 thousand quadrillion quadrillion', Number::forHumans(1000000000000000000000000000000000));

        $this->assertSame('0', Number::forHumans(0));
        $this->assertSame('0', Number::forHumans(0.0));
        $this->assertSame('0.00', Number::forHumans(0, 2));
        $this->assertSame('0.00', Number::forHumans(0.0, 2));
        $this->assertSame('-1', Number::forHumans(-1));
        $this->assertSame('-1.00', Number::forHumans(-1, precision: 2));
        $this->assertSame('-10', Number::forHumans(-10));
        $this->assertSame('-100', Number::forHumans(-100));
        $this->assertSame('-1 thousand', Number::forHumans(-1000));
        $this->assertSame('-1.23 thousand', Number::forHumans(-1234, precision: 2));
        $this->assertSame('-1.2 thousand', Number::forHumans(-1234, maxPrecision: 1));
        $this->assertSame('-1 million', Number::forHumans(-1000000));
        $this->assertSame('-1 billion', Number::forHumans(-1000000000));
        $this->assertSame('-1 trillion', Number::forHumans(-1000000000000));
        $this->assertSame('-1.1 trillion', Number::forHumans(-1100000000000, maxPrecision: 1));
        $this->assertSame('-1 quadrillion', Number::forHumans(-1000000000000000));
        $this->assertSame('-1 thousand quadrillion', Number::forHumans(-1000000000000000000));
    }

    public function testSummarize()
    {
        $this->assertSame('1', Number::abbreviate(1));
        $this->assertSame('1.00', Number::abbreviate(1, precision: 2));
        $this->assertSame('10', Number::abbreviate(10));
        $this->assertSame('100', Number::abbreviate(100));
        $this->assertSame('1K', Number::abbreviate(1000));
        $this->assertSame('1.00K', Number::abbreviate(1000, precision: 2));
        $this->assertSame('1K', Number::abbreviate(1000, maxPrecision: 2));
        $this->assertSame('1K', Number::abbreviate(1230));
        $this->assertSame('1.2K', Number::abbreviate(1230, maxPrecision: 1));
        $this->assertSame('1M', Number::abbreviate(1000000));
        $this->assertSame('1B', Number::abbreviate(1000000000));
        $this->assertSame('1T', Number::abbreviate(1000000000000));
        $this->assertSame('1Q', Number::abbreviate(1000000000000000));
        $this->assertSame('1KQ', Number::abbreviate(1000000000000000000));

        $this->assertSame('123', Number::abbreviate(123));
        $this->assertSame('1K', Number::abbreviate(1234));
        $this->assertSame('1.23K', Number::abbreviate(1234, precision: 2));
        $this->assertSame('12K', Number::abbreviate(12345));
        $this->assertSame('1M', Number::abbreviate(1234567));
        $this->assertSame('1B', Number::abbreviate(1234567890));
        $this->assertSame('1T', Number::abbreviate(1234567890123));
        $this->assertSame('1.23T', Number::abbreviate(1234567890123, precision: 2));
        $this->assertSame('1Q', Number::abbreviate(1234567890123456));
        $this->assertSame('1.23KQ', Number::abbreviate(1234567890123456789, precision: 2));
        $this->assertSame('490K', Number::abbreviate(489939));
        $this->assertSame('489.9390K', Number::abbreviate(489939, precision: 4));
        $this->assertSame('500.00000M', Number::abbreviate(500000000, precision: 5));

        $this->assertSame('1MQ', Number::abbreviate(1000000000000000000000));
        $this->assertSame('1BQ', Number::abbreviate(1000000000000000000000000));
        $this->assertSame('1TQ', Number::abbreviate(1000000000000000000000000000));
        $this->assertSame('1QQ', Number::abbreviate(1000000000000000000000000000000));
        $this->assertSame('1KQQ', Number::abbreviate(1000000000000000000000000000000000));

        $this->assertSame('0', Number::abbreviate(0));
        $this->assertSame('0', Number::abbreviate(0.0));
        $this->assertSame('0.00', Number::abbreviate(0, 2));
        $this->assertSame('0.00', Number::abbreviate(0.0, 2));
        $this->assertSame('-1', Number::abbreviate(-1));
        $this->assertSame('-1.00', Number::abbreviate(-1, precision: 2));
        $this->assertSame('-10', Number::abbreviate(-10));
        $this->assertSame('-100', Number::abbreviate(-100));
        $this->assertSame('-1K', Number::abbreviate(-1000));
        $this->assertSame('-1.23K', Number::abbreviate(-1234, precision: 2));
        $this->assertSame('-1.2K', Number::abbreviate(-1234, maxPrecision: 1));
        $this->assertSame('-1M', Number::abbreviate(-1000000));
        $this->assertSame('-1B', Number::abbreviate(-1000000000));
        $this->assertSame('-1T', Number::abbreviate(-1000000000000));
        $this->assertSame('-1.1T', Number::abbreviate(-1100000000000, maxPrecision: 1));
        $this->assertSame('-1Q', Number::abbreviate(-1000000000000000));
        $this->assertSame('-1KQ', Number::abbreviate(-1000000000000000000));
    }

    public function testLen()
    {
        $this->assertSame(1, Number::len(0));
        $this->assertSame(1, Number::len(1));
        $this->assertSame(1, Number::len(9));

        $this->assertSame(2, Number::len(10));
        $this->assertSame(2, Number::len(99));

        $this->assertSame(3, Number::len(100));
        $this->assertSame(3, Number::len(999));

        $this->assertSame(4, Number::len(1000));
        $this->assertSame(4, Number::len(9999));

        $this->assertSame(5, Number::len(10000));
        $this->assertSame(5, Number::len(99999));

        $this->assertSame(3, Number::len(0.1));
        $this->assertSame(3, Number::len(0.9));

        $this->assertSame(4, Number::len(-1.0));
        $this->assertSame(4, Number::len(-9.9));

        $this->assertNull(Number::len('Not a number'));
    }

    public function testRandom()
    {
        foreach (range(0, 100) as $i) {
            $random = Number::random();
            $this->assertIsInt($random);
            $this->assertGreaterThanOrEqual(0, $random);
            $this->assertLessThanOrEqual(PHP_INT_MAX, $random);
        }
    }

    public function testRandomWithMinAndMax()
    {
        foreach (range(0, 100) as $i) {
            $random = Number::randomBetween(10, 20);
            $this->assertIsInt($random);
            $this->assertGreaterThanOrEqual(10, $random);
            $this->assertLessThanOrEqual(20, $random);
        }
    }

    public function testGreatestCommonDivisor()
    {
        $this->assertSame(1, Number::gcd(1, 1));
        $this->assertSame(1, Number::gcd(1, 2));
        $this->assertSame(1, Number::gcd(2, 1));
        $this->assertSame(2, Number::gcd(2, 2));
        $this->assertSame(2, Number::gcd(2, 4));
        $this->assertSame(2, Number::gcd(4, 2));
        $this->assertSame(2, Number::gcd(4, 6));
        $this->assertSame(2, Number::gcd(6, 4));
        $this->assertSame(3, Number::gcd(3, 3));
        $this->assertSame(3, Number::gcd(3, 6));
        $this->assertSame(3, Number::gcd(6, 3));
        $this->assertSame(3, Number::gcd(6, 9));
        $this->assertSame(3, Number::gcd(9, 6));
        $this->assertSame(3, Number::gcd(9, 12));
        $this->assertSame(3, Number::gcd(12, 9));
        $this->assertSame(3, Number::gcd(12, 15));
        $this->assertSame(3, Number::gcd(15, 12));
        $this->assertSame(3, Number::gcd(15, 18));
        $this->assertSame(3, Number::gcd(18, 15));
    }

    public function testLowestCommonMultiplier()
    {
        $this->assertSame(1, Number::lcm(1, 1));
        $this->assertSame(2, Number::lcm(1, 2));
        $this->assertSame(2, Number::lcm(2, 1));
        $this->assertSame(2, Number::lcm(2, 2));
        $this->assertSame(4, Number::lcm(2, 4));
        $this->assertSame(4, Number::lcm(4, 2));
        $this->assertSame(12, Number::lcm(4, 6));
        $this->assertSame(12, Number::lcm(6, 4));
        $this->assertSame(3, Number::lcm(3, 3));
        $this->assertSame(6, Number::lcm(3, 6));
        $this->assertSame(6, Number::lcm(6, 3));
        $this->assertSame(6, Number::lcm(6, 9));
        $this->assertSame(6, Number::lcm(9, 6));
        $this->assertSame(12, Number::lcm(9, 12));
        $this->assertSame(12, Number::lcm(12, 9));
        $this->assertSame(12, Number::lcm(12, 15));
        $this->assertSame(12, Number::lcm(15, 12));
        $this->assertSame(30, Number::lcm(15, 18));
        $this->assertSame(30, Number::lcm(18, 15));
    }

    public function testFactorial()
    {
        $this->assertSame(1, Number::factorial(0));
        $this->assertSame(1, Number::factorial(1));
        $this->assertSame(2, Number::factorial(2));
        $this->assertSame(6, Number::factorial(3));
        $this->assertSame(24, Number::factorial(4));
        $this->assertSame(120, Number::factorial(5));
        $this->assertSame(720, Number::factorial(6));
        $this->assertSame(5040, Number::factorial(7));
        $this->assertSame(40320, Number::factorial(8));
        $this->assertSame(362880, Number::factorial(9));
        $this->assertSame(3628800, Number::factorial(10));
    }
}
