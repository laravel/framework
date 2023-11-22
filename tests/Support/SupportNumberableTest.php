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

    public function testNumberableFormat()
    {
        $this->assertSame('0',  $this->numberable(0)->format());
        $this->assertSame('1',  $this->numberable(1)->format());
        $this->assertSame('10', $this->numberable(10)->format());
        $this->assertSame('25', $this->numberable(25)->format());
        $this->assertSame('100', $this->numberable(100)->format());
        $this->assertSame('100,000', $this->numberable(100000)->format());
        $this->assertSame('100,000.00', $this->numberable(100000)->format(precision: 2));
        $this->assertSame('100,000.12', $this->numberable(100000.123)->format(precision: 2));
        $this->assertSame('100,000.123', $this->numberable(100000.1234)->format(maxPrecision: 3));
        $this->assertSame('100,000.124', $this->numberable(100000.1236)->format(maxPrecision: 3));
        $this->assertSame('123,456,789', $this->numberable(123456789)->format());

        $this->assertSame('-1', $this->numberable(-1)->format());
        $this->assertSame('-10', $this->numberable(-10)->format());
        $this->assertSame('-25', $this->numberable(-25)->format());

        $this->assertSame('0.2', $this->numberable(0.2)->format());
        $this->assertSame('0.20', $this->numberable(0.2)->format(precision: 2));
        $this->assertSame('0.123', $this->numberable(0.1234)->format(maxPrecision: 3));
        $this->assertSame('1.23', $this->numberable(1.23)->format());
        $this->assertSame('-1.23', $this->numberable(-1.23)->format());
        $this->assertSame('123.456', $this->numberable(123.456)->format());

        $this->assertSame('∞', $this->numberable(INF)->format());
        $this->assertSame('NaN', $this->numberable(NAN)->format());
    }

    public function testNumberableFormatWithDifferentLocale()
    {
        $this->assertSame('123,456,789', $this->numberable(123456789)->format(locale: 'en'));
        $this->assertSame('123.456.789', $this->numberable(123456789)->format(locale: 'de'));
        $this->assertSame('123 456 789', $this->numberable(123456789)->format(locale: 'fr'));
        $this->assertSame('123 456 789', $this->numberable(123456789)->format(locale: 'ru'));
        $this->assertSame('123 456 789', $this->numberable(123456789)->format(locale: 'sv'));
    }

    public function testNumberableFormatWithAppLocale()
    {
        $this->assertSame('123,456,789', $this->numberable(123456789)->format());

       $this->numberable()->useLocale('de');

        $this->assertSame('123.456.789', $this->numberable(123456789)->format());

       $this->numberable()->useLocale('en');
    }

    public function testNumberableSpellout()
    {
        $this->assertSame('ten', $this->numberable(10)->spell());
        $this->assertSame('one point two', $this->numberable(1.2)->spell());
    }

    public function testNumberableSpelloutWithLocale()
    {
        $this->assertSame('trois', $this->numberable(3)->spell('fr'));
    }

    public function testNumberableOrdinal()
    {
        $this->assertSame('1st', $this->numberable(1)->ordinal());
        $this->assertSame('2nd', $this->numberable(2)->ordinal());
        $this->assertSame('3rd', $this->numberable(3)->ordinal());
    }

    public function testNumberableToPercent()
    {
        $this->assertSame('0%', $this->numberable(0)->percentage(precision: 0));
        $this->assertSame('0%', $this->numberable(0)->percentage());
        $this->assertSame('1%', $this->numberable(1)->percentage());
        $this->assertSame('10.00%', $this->numberable(10)->percentage(precision: 2));
        $this->assertSame('100%', $this->numberable(100)->percentage());
        $this->assertSame('100.00%', $this->numberable(100)->percentage(precision: 2));
        $this->assertSame('100.123%', $this->numberable(100.1234)->percentage(maxPrecision: 3));

        $this->assertSame('300%', $this->numberable(300)->percentage());
        $this->assertSame('1,000%', $this->numberable(1000)->percentage());

        $this->assertSame('2%', $this->numberable(1.75)->percentage());
        $this->assertSame('1.75%', $this->numberable(1.75)->percentage(precision: 2));
        $this->assertSame('1.750%', $this->numberable(1.75)->percentage(precision: 3));
        $this->assertSame('0%', $this->numberable(0.12345)->percentage());
        $this->assertSame('0.00%', $this->numberable(0)->percentage(precision: 2));
        $this->assertSame('0.12%', $this->numberable(0.12345)->percentage(precision: 2));
        $this->assertSame('0.1235%', $this->numberable(0.12345)->percentage(precision: 4));
    }

    public function testNumberableToCurrency()
    {
        $this->assertSame('$0.00', $this->numberable(0)->currency());
        $this->assertSame('$1.00', $this->numberable(1)->currency());
        $this->assertSame('$10.00', $this->numberable(10)->currency());

        $this->assertSame('€0.00', $this->numberable(0)->currency('EUR'));
        $this->assertSame('€1.00', $this->numberable(1)->currency('EUR'));
        $this->assertSame('€10.00', $this->numberable(10)->currency('EUR'));

        $this->assertSame('-$5.00', $this->numberable(-5)->currency());
        $this->assertSame('$5.00', $this->numberable(5.00)->currency());
        $this->assertSame('$5.32', $this->numberable(5.325)->currency());
    }

    public function testNumberableToCurrencyWithDifferentLocale()
    {
        $this->assertSame('1,00 €', $this->numberable(1)->currency('EUR', 'de'));
        $this->assertSame('1,00 $', $this->numberable(1)->currency('USD', 'de'));
        $this->assertSame('1,00 £', $this->numberable(1)->currency('GBP', 'de'));

        $this->assertSame('123.456.789,12 $', $this->numberable(123456789.12345)->currency('USD', 'de'));
        $this->assertSame('123.456.789,12 €', $this->numberable(123456789.12345)->currency('EUR', 'de'));
        $this->assertSame('1 234,56 $US', $this->numberable(1234.56)->currency('USD', 'fr'));
    }

    public function testNumberableBytesToHuman()
    {
        $this->assertSame('0 B', $this->numberable(0)->fileSize());
        $this->assertSame('0.00 B', $this->numberable(0)->fileSize(precision: 2));
        $this->assertSame('1 B', $this->numberable(1)->fileSize());
        $this->assertSame('1 KB', $this->numberable(1024)->fileSize());
        $this->assertSame('2 KB', $this->numberable(2048)->fileSize());
        $this->assertSame('2.00 KB', $this->numberable(2048)->fileSize(precision: 2));
        $this->assertSame('1.23 KB', $this->numberable(1264)->fileSize(precision: 2));
        $this->assertSame('1.234 KB', $this->numberable(1264.12345)->fileSize(maxPrecision: 3));
        $this->assertSame('1.234 KB', $this->numberable(1264)->fileSize(3));
        $this->assertSame('5 GB', $this->numberable(1024 * 1024 * 1024 * 5)->fileSize());
        $this->assertSame('10 TB', $this->numberable((1024 ** 4) * 10)->fileSize());
        $this->assertSame('10 PB', $this->numberable((1024 ** 5) * 10)->fileSize());
        $this->assertSame('1 ZB', $this->numberable(1024 ** 7)->fileSize());
        $this->assertSame('1 YB', $this->numberable(1024 ** 8)->fileSize());
        $this->assertSame('1,024 YB', $this->numberable(1024 ** 9)->fileSize());
    }

    public function testNumberableToHuman()
    {
        $this->assertSame('1', $this->numberable(1)->forHumans());
        $this->assertSame('1.00', $this->numberable(1)->forHumans(precision: 2));
        $this->assertSame('10', $this->numberable(10)->forHumans());
        $this->assertSame('100', $this->numberable(100)->forHumans());
        $this->assertSame('1 thousand', $this->numberable(1000)->forHumans());
        $this->assertSame('1.00 thousand', $this->numberable(1000)->forHumans(precision: 2));
        $this->assertSame('1 thousand', $this->numberable(1000)->forHumans(maxPrecision: 2));
        $this->assertSame('1 thousand', $this->numberable(1230)->forHumans());
        $this->assertSame('1.2 thousand', $this->numberable(1230)->forHumans(maxPrecision: 1));
        $this->assertSame('1 million', $this->numberable(1000000)->forHumans());
        $this->assertSame('1 billion', $this->numberable(1000000000)->forHumans());
        $this->assertSame('1 trillion', $this->numberable(1000000000000)->forHumans());
        $this->assertSame('1 quadrillion', $this->numberable(1000000000000000)->forHumans());
        $this->assertSame('1 thousand quadrillion', $this->numberable(1000000000000000000)->forHumans());

        $this->assertSame('123', $this->numberable(123)->forHumans());
        $this->assertSame('1 thousand', $this->numberable(1234)->forHumans());
        $this->assertSame('1.23 thousand', $this->numberable(1234)->forHumans(precision: 2));
        $this->assertSame('12 thousand', $this->numberable(12345)->forHumans());
        $this->assertSame('1 million', $this->numberable(1234567)->forHumans());
        $this->assertSame('1 billion', $this->numberable(1234567890)->forHumans());
        $this->assertSame('1 trillion', $this->numberable(1234567890123)->forHumans());
        $this->assertSame('1.23 trillion', $this->numberable(1234567890123)->forHumans(precision: 2));
        $this->assertSame('1 quadrillion', $this->numberable(1234567890123456)->forHumans());
        $this->assertSame('1.23 thousand quadrillion', $this->numberable(1234567890123456789)->forHumans(precision: 2));
        $this->assertSame('490 thousand', $this->numberable(489939)->forHumans());
        $this->assertSame('489.9390 thousand', $this->numberable(489939)->forHumans(precision: 4));
        $this->assertSame('500.00000 million', $this->numberable(500000000)->forHumans(precision: 5));

        $this->assertSame('1 million quadrillion', $this->numberable(1000000000000000000000)->forHumans());
        $this->assertSame('1 billion quadrillion', $this->numberable(1000000000000000000000000)->forHumans());
        $this->assertSame('1 trillion quadrillion', $this->numberable(1000000000000000000000000000)->forHumans());
        $this->assertSame('1 quadrillion quadrillion', $this->numberable(1000000000000000000000000000000)->forHumans());
        $this->assertSame('1 thousand quadrillion quadrillion', $this->numberable(1000000000000000000000000000000000)->forHumans());

        $this->assertSame('0', $this->numberable(0)->forHumans());
        $this->assertSame('-1', $this->numberable(-1)->forHumans());
        $this->assertSame('-1.00', $this->numberable(-1)->forHumans(precision: 2));
        $this->assertSame('-10', $this->numberable(-10)->forHumans());
        $this->assertSame('-100', $this->numberable(-100)->forHumans());
        $this->assertSame('-1 thousand', $this->numberable(-1000)->forHumans());
        $this->assertSame('-1.23 thousand', $this->numberable(-1234)->forHumans(precision: 2));
        $this->assertSame('-1.2 thousand', $this->numberable(-1234)->forHumans(maxPrecision: 1));
        $this->assertSame('-1 million', $this->numberable(-1000000)->forHumans());
        $this->assertSame('-1 billion', $this->numberable(-1000000000)->forHumans());
        $this->assertSame('-1 trillion', $this->numberable(-1000000000000)->forHumans());
        $this->assertSame('-1.1 trillion', $this->numberable(-1100000000000)->forHumans(maxPrecision: 1));
        $this->assertSame('-1 quadrillion', $this->numberable(-1000000000000000)->forHumans());
        $this->assertSame('-1 thousand quadrillion', $this->numberable(-1000000000000000000)->forHumans());
    }
}
