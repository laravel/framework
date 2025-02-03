<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Number;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

class SupportNumberTest extends TestCase
{
    public function testDefaultLocale()
    {
        $this->assertSame('en', Number::defaultLocale());
    }

    public function testDefaultCurrency()
    {
        $this->assertSame('USD', Number::defaultCurrency());
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
    public function testSpellOrdinal()
    {
        $this->assertSame('first', Number::spellOrdinal(1));
        $this->assertSame('second', Number::spellOrdinal(2));
        $this->assertSame('third', Number::spellOrdinal(3));
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

    public function testPairs()
    {
        $this->assertSame([[1, 10], [11, 20], [21, 25]], Number::pairs(25, 10));
        $this->assertSame([[0, 10], [10, 20], [20, 25]], Number::pairs(25, 10, 0));
        $this->assertSame([[0, 2.5], [2.5, 5.0], [5.0, 7.5], [7.5, 10.0]], Number::pairs(10, 2.5, 0));
    }

    public function testTrim()
    {
        $this->assertSame(12, Number::trim(12));
        $this->assertSame(120, Number::trim(120));
        $this->assertSame(12, Number::trim(12.0));
        $this->assertSame(12.3, Number::trim(12.3));
        $this->assertSame(12.3, Number::trim(12.30));
        $this->assertSame(12.3456789, Number::trim(12.3456789));
        $this->assertSame(12.3456789, Number::trim(12.34567890000));
    }

    #[RequiresPhpExtension('intl')]
    public function testScientific()
    {
        $this->assertSame('1E0', Number::scientific(1));
        $this->assertSame('1E0', Number::scientific(1, precision: 0));
        $this->assertSame('1E0', Number::scientific(1, precision: 2));
        $this->assertSame('1.23E1', Number::scientific(12.34));
        $this->assertSame('1.23E1', Number::scientific(12.34, locale: 'en'));
        $this->assertSame('1,23E1', Number::scientific(12.34, locale: 'de'));
        $this->assertSame('1,23E1', Number::scientific(12.34, locale: 'fr'));
        $this->assertSame('1,23E1', Number::scientific(12.34, locale: 'ru'));
        $this->assertSame('1,23E1', Number::scientific(12.34, locale: 'sv'));
        $this->assertSame('1.23E1', Number::scientific(12.34, locale: 'ar'));
        $this->assertSame('1.23E1', Number::scientific(12.34, precision: 2));
        $this->assertSame('1.2346E1', Number::scientific(12.3456, precision: 4));

        // Large numbers
        $this->assertSame('1E6', Number::scientific(1000000));
        $this->assertSame('1E6', Number::scientific(1000000, precision: 0));
        $this->assertSame('1E6', Number::scientific(1000000, precision: 1));
        $this->assertSame('1E6', Number::scientific(1000000, exponent: 6));
        $this->assertSame('10E5', Number::scientific(1000000, exponent: 5));
        $this->assertSame('100E4', Number::scientific(1000000, exponent: 4, precision: 1));
        $this->assertSame('1E6', Number::scientific(1000000, locale: 'en'));
        $this->assertSame('10E5', Number::scientific(1000000, exponent: 5, locale: 'en'));
        $this->assertSame('1E6', Number::scientific(1000000, locale: 'fr'));
        $this->assertSame('10E5', Number::scientific(1000000, exponent: 5, locale: 'fr'));

        $this->assertSame('1.23E6', Number::scientific(1234567));
        $this->assertSame('1E6', Number::scientific(1234567, precision: 0));
        $this->assertSame('1.2E6', Number::scientific(1234567, precision: 1));
        $this->assertSame('12.35E5', Number::scientific(1234567, exponent: 5));
        $this->assertSame('123.46E4', Number::scientific(1234567, exponent: 4));
        $this->assertSame('1234.57E3', Number::scientific(1234567, exponent: 3));
        $this->assertSame('12345.67E2', Number::scientific(1234567, exponent: 2));
        $this->assertSame('123456.7E1', Number::scientific(1234567, exponent: 1));
        $this->assertSame('1234567E0', Number::scientific(1234567, exponent: 0));
        $this->assertSame('12.3E5', Number::scientific(1234567, exponent:5, precision: 1));
        $this->assertSame('123.5E4', Number::scientific(1234567, exponent: 4, precision: 1));
        $this->assertSame('12.35E5', Number::scientific(1234567, exponent:5, locale: 'en'));
        $this->assertSame('123,46E4', Number::scientific(1234567, exponent: 4, locale: 'de'));
        $this->assertSame('123,46E4', Number::scientific(1234567, exponent: 4, locale: 'fr'));
        $this->assertSame('1,23E6', Number::scientific(1234567, locale: 'ru'));
        $this->assertSame('1,23E6', Number::scientific(1234567, locale: 'sv'));
        $this->assertSame('1.23E6', Number::scientific(1234567, locale: 'ar'));

        $this->assertSame('1.23E8', Number::scientific(123456789));
        $this->assertSame('0.12E9', Number::scientific(123456789, exponent: 9));
        $this->assertSame('1.23E8', Number::scientific(123456789, exponent: 8));
        $this->assertSame('1234.57E5', Number::scientific(123456789, exponent: 5));
        $this->assertSame('1.2346E8', Number::scientific(123456789, exponent: 8, precision: 4));
        $this->assertSame('1.23456789E8', Number::scientific(123456789, exponent: 8, precision: 15));
        $this->assertSame('123456.789E3', Number::scientific(123456789, exponent: 3, precision: 15));
        $this->assertSame('1E8', Number::scientific(123456789, precision: 0));
        $this->assertSame('1.2E8', Number::scientific(123456789, precision: 1));
        $this->assertSame('1.23E8', Number::scientific(123456789, locale: 'en'));
        $this->assertSame('1,23E8', Number::scientific(123456789, locale: 'de'));
        $this->assertSame('1,23E8', Number::scientific(123456789, locale: 'fr'));
        $this->assertSame('123456,789E3', Number::scientific(123456789, exponent: 3, precision: 15, locale: 'fr'));
        $this->assertSame('1,23E8', Number::scientific(123456789, locale: 'ru'));
        $this->assertSame('1.23E8', Number::scientific(123456789, locale: 'ar'));

        // Small numbers
        $this->assertSame('1E-3', Number::scientific(0.001));
        $this->assertSame('1E-3', Number::scientific(0.001, exponent: -3));
        $this->assertSame('10E-4', Number::scientific(0.001, exponent: -4));
        $this->assertSame('10E-4', Number::scientific(0.001, exponent: -4, precision: 0));
        $this->assertSame('10E-4', Number::scientific(0.001, exponent: -4, precision: 1));
        $this->assertSame('20E-4', Number::scientific(0.002, exponent: -4));
        $this->assertSame('8.23E-3', Number::scientific(0.00823, exponent: -3));
        $this->assertSame('1E-3', Number::scientific(0.001, precision: 0));
        $this->assertSame('1E-3', Number::scientific(0.001, precision: 1));
        $this->assertSame('1E-3', Number::scientific(0.001, locale: 'en'));
        $this->assertSame('1.1E-3', Number::scientific(0.0011, exponent: -3, locale: 'en'));
        $this->assertSame('1E-3', Number::scientific(0.001, locale: 'fr'));
        $this->assertSame('1,1E-3', Number::scientific(0.0011, exponent: -3, locale: 'fr'));

        $this->assertSame('1.23E-3', Number::scientific(0.00123));
        $this->assertSame('1.23E-6', Number::scientific(0.000001234));
        $this->assertSame('1.23E-6', Number::scientific(0.000001234, exponent: -6));
        $this->assertSame('123.4E-8', Number::scientific(0.000001234, exponent: -8));
        $this->assertSame('1234E-9', Number::scientific(0.000001234, exponent: -9));
        $this->assertSame('12340E-10', Number::scientific(0.000001234, exponent: -10));
        $this->assertSame('1E-6', Number::scientific(0.000001234, precision: 0));
        $this->assertSame('1.2E-6', Number::scientific(0.000001234, precision: 1));
        $this->assertSame('1.234E-6', Number::scientific(0.000001234, exponent: -6, precision: 3));
        $this->assertSame('1.234E-6', Number::scientific(0.000001234, exponent: -6, precision: 4));
        $this->assertSame('1.23E-6', Number::scientific(0.000001234, locale: 'en'));
        $this->assertSame('1,23E-6', Number::scientific(0.000001234, locale: 'de'));
        $this->assertSame('1,23E-6', Number::scientific(0.000001234, locale: 'fr'));
        $this->assertSame('1,23E-6', Number::scientific(0.000001234, locale: 'ru'));
        $this->assertSame('1,23E-6', Number::scientific(0.000001234, locale: 'sv'));
        $this->assertSame("1.23E-6", Number::scientific(0.000001234, locale: 'ar'));

        // Zero and special values
        $this->assertSame('0E0', Number::scientific(0));
        $this->assertSame('0E0', Number::scientific(0, exponent: 0));
        $this->assertSame('0E0', Number::scientific(0, precision: 0));
        $this->assertSame('0E0', Number::scientific(0, precision: 1));
        $this->assertSame('0E0', Number::scientific(0, locale: 'fr'));

        $this->assertSame('∞', Number::scientific(INF));
        $this->assertSame('∞', Number::scientific(INF, precision: 0));
        $this->assertSame('∞', Number::scientific(INF, precision: 1));
        $this->assertSame('∞', Number::scientific(INF, locale: 'en'));
        $this->assertSame('∞', Number::scientific(INF, locale: 'de'));
        $this->assertSame('∞', Number::scientific(INF, locale: 'fr'));
        $this->assertSame('∞', Number::scientific(INF, locale: 'ru'));
        $this->assertSame('∞', Number::scientific(INF, locale: 'sv'));
        $this->assertSame('∞', Number::scientific(INF, locale: 'ar'));

        $this->assertSame('NaN', Number::scientific(NAN));
        $this->assertSame('NaN', Number::scientific(NAN, precision: 0));
        $this->assertSame('NaN', Number::scientific(NAN, precision: 1));
        $this->assertSame('NaN', Number::scientific(NAN, locale: 'en'));
        $this->assertSame('NaN', Number::scientific(NAN, locale: 'de'));
        $this->assertSame('NaN', Number::scientific(NAN, locale: 'fr'));
        $this->assertSame("не\u{A0}число", Number::scientific(NAN, locale: 'ru'));
        $this->assertSame('NaN', Number::scientific(NAN, locale: 'sv'));
        $this->assertSame("ليس\u{A0}رقمًا", Number::scientific(NAN, locale: 'ar'));

        // Negative numbers
        $this->assertSame('-1E0', Number::scientific(-1));
        $this->assertSame('-1E0', Number::scientific(-1, exponent: 0));
        $this->assertSame('-0.1E1', Number::scientific(-1, exponent: 1));
        $this->assertSame('-0.01E2', Number::scientific(-1, exponent: 2));
        $this->assertSame('-10E-1', Number::scientific(-1, exponent: -1));
        $this->assertSame('-100E-2', Number::scientific(-1, exponent: -2));
        $this->assertSame('-0.01E2', Number::scientific(-1, exponent: 2, precision: 4));
        $this->assertSame('-1E0', Number::scientific(-1, precision: 0));
        $this->assertSame('-1E0', Number::scientific(-1, precision: 1));
        $this->assertSame('-1E0', Number::scientific(-1, locale: 'en'));
        $this->assertSame('-1E0', Number::scientific(-1, locale: 'fr'));

        $this->assertSame('-1.23E1', Number::scientific(-12.34));
        $this->assertSame('-1.23E1', Number::scientific(-12.34, exponent: 1));
        $this->assertSame('-0.12E2', Number::scientific(-12.34, exponent: 2));
        $this->assertSame('-123.4E-1', Number::scientific(-12.34, exponent: -1));
        $this->assertSame('-1234E-2', Number::scientific(-12.34, exponent: -2));
        $this->assertSame('-1.234E1', Number::scientific(-12.34, precision: 3));
        $this->assertSame('-1.2E1', Number::scientific(-12.34, precision: 1));
        $this->assertSame('-1.23E1', Number::scientific(-12.34, locale: 'en'));
        $this->assertSame('-1,23E1', Number::scientific(-12.34, locale: 'fr'));

        $this->assertSame('-1.23E-3', Number::scientific(-0.00123));
        $this->assertSame('-1.23E-3', Number::scientific(-0.00123, exponent: -3));
        $this->assertSame('-12.3E-4', Number::scientific(-0.00123, exponent: -4));
        $this->assertSame('-1E-3', Number::scientific(-0.00123, precision: 0));
        $this->assertSame('-1.2E-3', Number::scientific(-0.00123, precision: 1));
        $this->assertSame('-1.23E-3', Number::scientific(-0.00123, locale: 'en'));
        $this->assertSame('-1,23E-3', Number::scientific(-0.00123, locale: 'fr'));
    }
}
