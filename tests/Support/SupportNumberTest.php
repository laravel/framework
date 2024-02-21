<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Number;
use PHPUnit\Framework\TestCase;

class SupportNumberTest extends TestCase
{
    public function testFormat()
    {
        $this->needsIntlExtension();

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

    public function testFormatWithDifferentLocale()
    {
        $this->needsIntlExtension();

        $this->assertSame('123,456,789', Number::format(123456789, locale: 'en'));
        $this->assertSame('123.456.789', Number::format(123456789, locale: 'de'));
        $this->assertSame('123 456 789', Number::format(123456789, locale: 'fr'));
        $this->assertSame('123 456 789', Number::format(123456789, locale: 'ru'));
        $this->assertSame('123 456 789', Number::format(123456789, locale: 'sv'));
    }

    public function testFormatWithAppLocale()
    {
        $this->needsIntlExtension();

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

    public function testSpelloutWithLocale()
    {
        $this->needsIntlExtension();

        $this->assertSame('trois', Number::spell(3, 'fr'));
    }

    public function testSpelloutWithThreshold()
    {
        $this->needsIntlExtension();

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

    public function testToPercent()
    {
        $this->needsIntlExtension();

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

    public function testToCurrency()
    {
        $this->needsIntlExtension();

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

    public function testToCurrencyWithDifferentLocale()
    {
        $this->needsIntlExtension();

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

    protected function needsIntlExtension()
    {
        if (! extension_loaded('intl')) {
            $this->markTestSkipped('The intl extension is not installed. Please install the extension to enable '.__CLASS__);
        }
    }
}
