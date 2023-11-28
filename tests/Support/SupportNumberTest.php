<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Number;
use PHPUnit\Framework\TestCase;

class SupportNumberTest extends TestCase
{
    public function testFormat()
    {
        $this->needsIntlExtension();

        $this->assertSame('0', Number::create(0)->format());
        $this->assertSame('1', Number::create(1)->format());
        $this->assertSame('10', Number::create(10)->format());
        $this->assertSame('25', Number::create(25)->format());
        $this->assertSame('100', Number::create(100)->format());
        $this->assertSame('100,000', Number::create(100000)->format());
        $this->assertSame('100,000.00', Number::create(100000)->format(precision: 2));
        $this->assertSame('100,000.12', Number::create(100000.123)->format(precision: 2));
        $this->assertSame('100,000.123', Number::create(100000.1234)->format(maxPrecision: 3));
        $this->assertSame('100,000.124', Number::create(100000.1236)->format(maxPrecision: 3));
        $this->assertSame('123,456,789', Number::create(123456789)->format());

        $this->assertSame('-1', Number::create(-1)->format());
        $this->assertSame('-10', Number::create(-10)->format());
        $this->assertSame('-25', Number::create(-25)->format());

        $this->assertSame('0.2', Number::create(0.2)->format());
        $this->assertSame('0.20', Number::create(0.2)->format(precision: 2));
        $this->assertSame('0.123', Number::create(0.1234)->format(maxPrecision: 3));
        $this->assertSame('1.23', Number::create(1.23)->format());
        $this->assertSame('-1.23', Number::create(-1.23)->format());
        $this->assertSame('123.456', Number::create(123.456)->format());

        $this->assertSame('∞', Number::create(INF)->format());
        $this->assertSame('NaN', Number::create(NAN)->format());
    }

    public function testFormatWithDifferentLocale()
    {
        $this->needsIntlExtension();

        $this->assertSame('123,456,789', Number::create(123456789, 'en')->format());
        $this->assertSame('123.456.789', Number::create(123456789, 'de')->format());
        $this->assertSame('123 456 789', Number::create(123456789, 'fr')->format());
        $this->assertSame('123 456 789', Number::create(123456789, 'ru')->format());
        $this->assertSame('123 456 789', Number::create(123456789, 'sv')->format());
    }

    public function testFormatWithAppLocale()
    {
        $this->needsIntlExtension();

        $this->assertSame('123,456,789', Number::create(123456789)->format());

        Number::useLocale('de');

        $this->assertSame('123.456.789', Number::create(123456789)->format());

        Number::useLocale('en');
    }

    public function testSpellout()
    {
        $this->assertSame('ten', Number::create(10)->toSpell());
        $this->assertSame('one point two', Number::create(1.2)->toSpell());
    }

    public function testSpelloutWithLocale()
    {
        $this->needsIntlExtension();

        $this->assertSame('trois', Number::create(3, 'fr')->toSpell());
    }

    public function testOrdinal()
    {
        $this->assertSame('1st', Number::create(1)->toOrdinal());
        $this->assertSame('2nd', Number::create(2)->toOrdinal());
        $this->assertSame('3rd', Number::create(3)->toOrdinal());
    }

    public function testToPercent()
    {
        $this->needsIntlExtension();

        $this->assertSame('0%', Number::create(0)->toPercentage(precision: 0));
        $this->assertSame('0%', Number::create(0)->toPercentage());
        $this->assertSame('1%', Number::create(1)->toPercentage());
        $this->assertSame('10.00%', Number::create(10)->toPercentage(precision: 2));
        $this->assertSame('100%', Number::create(100)->toPercentage());
        $this->assertSame('100.00%', Number::create(100)->toPercentage(precision: 2));
        $this->assertSame('100.123%', Number::create(100.1234)->toPercentage(maxPrecision: 3));

        $this->assertSame('300%', Number::create(300)->toPercentage());
        $this->assertSame('1,000%', Number::create(1000)->toPercentage());

        $this->assertSame('2%', Number::create(1.75)->toPercentage());
        $this->assertSame('1.75%', Number::create(1.75)->toPercentage(precision: 2));
        $this->assertSame('1.750%', Number::create(1.75)->toPercentage(precision: 3));
        $this->assertSame('0%', Number::create(0.12345)->toPercentage());
        $this->assertSame('0.00%', Number::create(0)->toPercentage(precision: 2));
        $this->assertSame('0.12%', Number::create(0.12345)->toPercentage(precision: 2));
        $this->assertSame('0.1235%', Number::create(0.12345)->toPercentage(precision: 4));
    }

    public function testToCurrency()
    {
        $this->needsIntlExtension();

        $this->assertSame('$0.00', Number::create(0)->toCurrency());
        $this->assertSame('$1.00', Number::create(1)->toCurrency());
        $this->assertSame('$10.00', Number::create(10)->toCurrency());

        $this->assertSame('€0.00', Number::create(0)->toCurrency('EUR'));
        $this->assertSame('€1.00', Number::create(1)->toCurrency('EUR'));
        $this->assertSame('€10.00', Number::create(10)->toCurrency('EUR'));

        $this->assertSame('-$5.00', Number::create(-5)->toCurrency());
        $this->assertSame('$5.00', Number::create(5.00)->toCurrency());
        $this->assertSame('$5.32', Number::create(5.325)->toCurrency());
    }

    public function testToCurrencyWithDifferentLocale()
    {
        $this->needsIntlExtension();

        $this->assertSame('1,00 €', Number::create(1, 'de')->toCurrency('EUR'));
        $this->assertSame('1,00 $', Number::create(1, 'de')->toCurrency('USD'));
        $this->assertSame('1,00 £', Number::create(1, 'de')->toCurrency('GBP'));

        $this->assertSame('123.456.789,12 $', Number::create(123456789.12345, 'de')->toCurrency('USD'));
        $this->assertSame('123.456.789,12 €', Number::create(123456789.12345, 'de')->toCurrency('EUR'));
        $this->assertSame('1 234,56 $US', Number::create(1234.56, 'fr')->toCurrency('USD'));
    }

    public function testBytesToHuman()
    {
        $this->assertSame('0 B', Number::create(0)->toFileSize());
        $this->assertSame('0.00 B', Number::create(0)->toFileSize(precision: 2));
        $this->assertSame('1 B', Number::create(1)->toFileSize());
        $this->assertSame('1 KB', Number::create(1024)->toFileSize());
        $this->assertSame('2 KB', Number::create(2048)->toFileSize());
        $this->assertSame('2.00 KB', Number::create(2048)->toFileSize(precision: 2));
        $this->assertSame('1.23 KB', Number::create(1264)->toFileSize(precision: 2));
        $this->assertSame('1.234 KB', Number::create(1264.12345)->toFileSize(maxPrecision: 3));
        $this->assertSame('1.234 KB', Number::create(1264, 3)->toFileSize());
        $this->assertSame('5 GB', Number::create(1024 * 1024 * 1024 * 5)->toFileSize());
        $this->assertSame('10 TB', Number::create((1024 ** 4) * 10)->toFileSize());
        $this->assertSame('10 PB', Number::create((1024 ** 5) * 10)->toFileSize());
        $this->assertSame('1 ZB', Number::create(1024 ** 7)->toFileSize());
        $this->assertSame('1 YB', Number::create(1024 ** 8)->toFileSize());
        $this->assertSame('1,024 YB', Number::create(1024 ** 9)->toFileSize());
    }

    public function testToHuman()
    {
        $this->assertSame('1', Number::create(1)->toHumans());
        $this->assertSame('1.00', Number::create(1)->toHumans(precision: 2));
        $this->assertSame('10', Number::create(10)->toHumans());
        $this->assertSame('100', Number::create(100)->toHumans());
        $this->assertSame('1 thousand', Number::create(1000)->toHumans());
        $this->assertSame('1.00 thousand', Number::create(1000)->toHumans(precision: 2));
        $this->assertSame('1 thousand', Number::create(1000)->toHumans(maxPrecision: 2));
        $this->assertSame('1 thousand', Number::create(1230)->toHumans());
        $this->assertSame('1.2 thousand', Number::create(1230)->toHumans(maxPrecision: 1));
        $this->assertSame('1 million', Number::create(1000000)->toHumans());
        $this->assertSame('1 billion', Number::create(1000000000)->toHumans());
        $this->assertSame('1 trillion', Number::create(1000000000000)->toHumans());
        $this->assertSame('1 quadrillion', Number::create(1000000000000000)->toHumans());
        $this->assertSame('1 thousand quadrillion', Number::create(1000000000000000000)->toHumans());

        $this->assertSame('123', Number::create(123)->toHumans());
        $this->assertSame('1 thousand', Number::create(1234)->toHumans());
        $this->assertSame('1.23 thousand', Number::create(1234)->toHumans(precision: 2));
        $this->assertSame('12 thousand', Number::create(12345)->toHumans());
        $this->assertSame('1 million', Number::create(1234567)->toHumans());
        $this->assertSame('1 billion', Number::create(1234567890)->toHumans());
        $this->assertSame('1 trillion', Number::create(1234567890123)->toHumans());
        $this->assertSame('1.23 trillion', Number::create(1234567890123)->toHumans(precision: 2));
        $this->assertSame('1 quadrillion', Number::create(1234567890123456)->toHumans());
        $this->assertSame('1.23 thousand quadrillion', Number::create(1234567890123456789)->toHumans(precision: 2));
        $this->assertSame('490 thousand', Number::create(489939)->toHumans());
        $this->assertSame('489.9390 thousand', Number::create(489939)->toHumans(precision: 4));
        $this->assertSame('500.00000 million', Number::create(500000000)->toHumans(precision: 5));

        $this->assertSame('1 million quadrillion', Number::create(1000000000000000000000)->toHumans());
        $this->assertSame('1 billion quadrillion', Number::create(1000000000000000000000000)->toHumans());
        $this->assertSame('1 trillion quadrillion', Number::create(1000000000000000000000000000)->toHumans());
        $this->assertSame('1 quadrillion quadrillion', Number::create(1000000000000000000000000000000)->toHumans());
        $this->assertSame('1 thousand quadrillion quadrillion', Number::create(1000000000000000000000000000000000)->toHumans());

        $this->assertSame('0', Number::create(0)->toHumans());
        $this->assertSame('-1', Number::create(-1)->toHumans());
        $this->assertSame('-1.00', Number::create(-1)->toHumans(precision: 2));
        $this->assertSame('-10', Number::create(-10)->toHumans());
        $this->assertSame('-100', Number::create(-100)->toHumans());
        $this->assertSame('-1 thousand', Number::create(-1000)->toHumans());
        $this->assertSame('-1.23 thousand', Number::create(-1234)->toHumans(precision: 2));
        $this->assertSame('-1.2 thousand', Number::create(-1234)->toHumans(maxPrecision: 1));
        $this->assertSame('-1 million', Number::create(-1000000)->toHumans());
        $this->assertSame('-1 billion', Number::create(-1000000000)->toHumans());
        $this->assertSame('-1 trillion', Number::create(-1000000000000)->toHumans());
        $this->assertSame('-1.1 trillion', Number::create(-1100000000000)->toHumans(maxPrecision: 1));
        $this->assertSame('-1 quadrillion', Number::create(-1000000000000000)->toHumans());
        $this->assertSame('-1 thousand quadrillion', Number::create(-1000000000000000000)->toHumans());
    }

    protected function needsIntlExtension()
    {
        if (! extension_loaded('intl')) {
            $this->markTestSkipped('The intl extension is not installed. Please install the extension to enable ' . __CLASS__);
        }
    }
}
