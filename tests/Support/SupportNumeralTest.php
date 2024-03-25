<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Number;
use Illuminate\Support\Numeral;
use PHPUnit\Framework\TestCase;

class SupportNumeralTest extends TestCase
{
    protected function numeral($value = 0)
    {
        return new Numeral($value);
    }

    public function testEven()
    {
        $this->assertTrue($this->numeral(2)->isEven());
        $this->assertFalse($this->numeral(3)->isEven());
    }

    public function testOdd()
    {
        $this->assertTrue($this->numeral(3)->isOdd());
        $this->assertFalse($this->numeral(2)->isOdd());
    }

    public function testFloat()
    {
        $this->assertTrue($this->numeral(11.0)->isFloat());
        $this->assertFalse($this->numeral(3)->isFloat());
    }

    public function testInt()
    {
        $this->assertTrue($this->numeral(3)->isInt());
        $this->assertFalse($this->numeral(11.0)->isInt());
    }

    public function testPositive()
    {
        $this->assertTrue($this->numeral(3)->isPositive());
        $this->assertFalse($this->numeral(-3)->isPositive());
    }

    public function testPositiveInt()
    {
        $this->assertTrue($this->numeral(3)->isPositiveInt());
        $this->assertFalse($this->numeral(-3)->isPositiveInt());
        $this->assertFalse($this->numeral(11.0)->isPositiveInt());
    }

    public function testPositiveFloat()
    {
        $this->assertTrue($this->numeral(11.0)->isPositiveFloat());
        $this->assertFalse($this->numeral(-11.0)->isPositiveFloat());
        $this->assertFalse($this->numeral(3)->isPositiveFloat());
    }

    public function testNegative()
    {
        $this->assertTrue($this->numeral(-3)->isNegative());
        $this->assertFalse($this->numeral(3)->isNegative());
    }

    public function testNegativeInt()
    {
        $this->assertTrue($this->numeral(-3)->isNegativeInt());
        $this->assertFalse($this->numeral(3)->isNegativeInt());
        $this->assertFalse($this->numeral(11.0)->isNegativeInt());
    }

    public function testNegativeFloat()
    {
        $this->assertTrue($this->numeral(-11.0)->isNegativeFloat());
        $this->assertFalse($this->numeral(11.0)->isNegativeFloat());
        $this->assertFalse($this->numeral(3)->isNegativeFloat());
    }

    public function testZero()
    {
        $this->assertTrue($this->numeral(0)->isZero());
        $this->assertFalse($this->numeral(3)->isZero());
        $this->assertFalse($this->numeral(-3)->isZero());
    }

    public function testNegate()
    {
        $this->assertSame(-3, $this->numeral(3)->negate()->value());
        $this->assertSame(3, $this->numeral(-3)->negate()->value());

        $this->assertSame(0, $this->numeral(0)->negate()->value());

        $this->assertSame(-11.0, $this->numeral(11.0)->negate()->value());
        $this->assertSame(11.0, $this->numeral(-11.0)->negate()->value());
    }

    #[RequiresPhpExtension('intl')]
    public function testFormat()
    {
        $this->assertSame('0', $this->numeral(0)->format()->value());
        $this->assertSame('0', $this->numeral(0.0)->format()->value());
        $this->assertSame('0', $this->numeral(0.00)->format()->value());
        $this->assertSame('1', $this->numeral(1)->format()->value());
        $this->assertSame('10', $this->numeral(10)->format()->value());
        $this->assertSame('25', $this->numeral(25)->format()->value());
        $this->assertSame('100', $this->numeral(100)->format()->value());
        $this->assertSame('100,000', $this->numeral(100000)->format()->value());
        $this->assertSame('100,000.00', $this->numeral(100000)->format(2)->value());
        $this->assertSame('100,000.12', $this->numeral(100000.123)->format(2)->value());
        $this->assertSame('100,000.123', $this->numeral(100000.1234)->format(maxPrecision: 3)->value());
        $this->assertSame('100,000.124', $this->numeral(100000.1236)->format(maxPrecision: 3)->value());
        $this->assertSame('123,456,789', $this->numeral(123456789)->format()->value());

        $this->assertSame('-1', $this->numeral(-1)->format());
        $this->assertSame('-10', $this->numeral(-10)->format());
        $this->assertSame('-25', $this->numeral(-25)->format());

        $this->assertSame('0.2', $this->numeral(0.2)->format());
        $this->assertSame('0.20', $this->numeral(0.2)->format(2));
        $this->assertSame('0.123', $this->numeral(0.1234)->format(maxPrecision: 3));
        $this->assertSame('1.23', $this->numeral(1.23)->format());
        $this->assertSame('-1.23', $this->numeral(-1.23)->format());
        $this->assertSame('123.456', $this->numeral(123.456)->format());

        $this->assertSame('∞', $this->numeral(INF)->format());
        $this->assertSame('NaN', $this->numeral(NAN)->format());
    }

    #[RequiresPhpExtension('intl')]
    public function testFormatWithDifferentLocale()
    {
        $this->assertSame('123,456,789', $this->numeral(123456789)->format(locale: 'en')->value());
        $this->assertSame('123.456.789', $this->numeral(123456789)->format(locale: 'de')->value());
        $this->assertSame('123 456 789', $this->numeral(123456789)->format(123456789, locale: 'fr')->value());
        $this->assertSame('123 456 789', $this->numeral(123456789)->format(locale: 'ru')->value());
        $this->assertSame('123 456 789', $this->numeral(123456789)->format(locale: 'sv')->value());
    }

    #[RequiresPhpExtension('intl')]
    public function testFormatWithAppLocale()
    {
        $this->assertSame('123,456,789', $this->numeral(123456789)->format()->value());

        Number::useLocale('de');

        $this->assertSame('123.456.789', $this->numeral(123456789)->format()->value());

        Number::useLocale('en');
    }

    public function testSpellout()
    {
        $this->assertSame('ten', $this->numeral()->spell(10)->value());
        $this->assertSame('one point two', $this->numeral()->spell(1.2)->value());
    }

    #[RequiresPhpExtension('intl')]
    public function testSpelloutWithLocale()
    {
        $this->assertSame('trois', $this->numeral()->spell(3, 'fr')->value());
    }

    #[RequiresPhpExtension('intl')]
    public function testSpelloutWithThreshold()
    {
        $this->assertSame('9', $this->numeral(9)->spell(after: 10)->value());
        $this->assertSame('10', $this->numeral(10)->spell(after: 10)->value());
        $this->assertSame('eleven', $this->numeral(11)->spell(after: 10)->value());

        $this->assertSame('nine', $this->numeral(9)->spell(until: 10)->value());
        $this->assertSame('10', $this->numeral(10)->spell(until: 10)->value());
        $this->assertSame('11', $this->numeral(11)->spell(until: 10)->value());

        $this->assertSame('ten thousand', $this->numeral(10000)->spell(until: 50000)->value());
        $this->assertSame('100,000', $this->numeral(100000)->spell(until: 50000)->value());
    }

    public function testOrdinal()
    {
        $this->assertSame('1st', $this->numeral(1)->ordinal()->value());
        $this->assertSame('2nd', $this->numeral(2)->ordinal()->value());
        $this->assertSame('3rd', $this->numeral(3)->ordinal()->value());
    }

    #[RequiresPhpExtension('intl')]
    public function testToPercent()
    {
        $this->assertSame('0%', $this->numeral(0)->percentage(0)->value());
        $this->assertSame('0%', $this->numeral(0)->percentage()->value());
        $this->assertSame('1%', $this->numeral(1)->percentage()->value());
        $this->assertSame('10.00%', $this->numeral(10)->percentage(2)->value());
        $this->assertSame('100%', $this->numeral(100)->percentage()->value());
        $this->assertSame('100.00%', $this->numeral(100)->percentage(2)->value());
        $this->assertSame('100.123%', $this->numeral(100.1234)->percentage(maxPrecision: 3)->value());

        $this->assertSame('300%', $this->numeral(300)->percentage()->value());
        $this->assertSame('1,000%', $this->numeral(1000)->percentage()->value());

        $this->assertSame('2%', $this->numeral(1.75)->percentage()->value());
        $this->assertSame('1.75%', $this->numeral(1.75)->percentage(2)->value());
        $this->assertSame('1.750%', $this->numeral(1.75)->percentage(3)->value());
        $this->assertSame('0%', $this->numeral(0.12345)->percentage()->value());
        $this->assertSame('0.00%', $this->numeral(0)->percentage(2)->value());
        $this->assertSame('0.12%', $this->numeral(0.12345)->percentage(2)->value());
        $this->assertSame('0.1235%', $this->numeral(0.12345)->percentage(4)->value());
    }

    #[RequiresPhpExtension('intl')]
    public function testToCurrency()
    {
        $this->assertSame('$0.00', $this->numeral(0)->currency()->value());
        $this->assertSame('$1.00', $this->numeral(1)->currency()->value());
        $this->assertSame('$10.00', $this->numeral(10)->currency()->value());

        $this->assertSame('€0.00', $this->numeral(0)->currency('EUR')->value());
        $this->assertSame('€1.00', $this->numeral(1)->currency('EUR')->value());
        $this->assertSame('€10.00', $this->numeral(10)->currency('EUR')->value());

        $this->assertSame('-$5.00', $this->numeral(-5)->currency()->value());
        $this->assertSame('$5.00', $this->numeral(5.00)->currency()->value());
        $this->assertSame('$5.32', $this->numeral(5.325)->currency()->value());
    }

    #[RequiresPhpExtension('intl')]
    public function testToCurrencyWithDifferentLocale()
    {
        $this->assertSame('1,00 €', $this->numeral(1)->currency('EUR', 'de')->value());
        $this->assertSame('1,00 $', $this->numeral(1)->currency('USD', 'de')->value());
        $this->assertSame('1,00 £', $this->numeral(1)->currency('GBP', 'de')->value());

        $this->assertSame('123.456.789,12 $', $this->numeral(123456789.12345)->currency('USD', 'de')->value());
        $this->assertSame('123.456.789,12 €', $this->numeral(123456789.12345)->currency('EUR', 'de')->value());
        $this->assertSame('1 234,56 $US', $this->numeral(1234.56)->currency('USD', 'fr')->value());
    }

    public function testBytesToHuman()
    {
        $this->assertSame('0 B', $this->numeral(0)->fileSize()->value());
        $this->assertSame('0.00 B', $this->numeral(0)->fileSize(2)->value());
        $this->assertSame('1 B', $this->numeral(1)->fileSize()->value());
        $this->assertSame('1 KB', $this->numeral(1024)->fileSize()->value());
        $this->assertSame('2 KB', $this->numeral(2048)->fileSize()->value());
        $this->assertSame('2.00 KB', $this->numeral(2048)->fileSize(2)->value());
        $this->assertSame('1.23 KB', $this->numeral(1264)->fileSize(2)->value());
        $this->assertSame('1.234 KB', $this->numeral(1264.12345)->fileSize(maxPrecision: 3)->value());
        $this->assertSame('1.234 KB', $this->numeral(1264)->fileSize(3)->value());
        $this->assertSame('5 GB', $this->numeral(1024 * 1024 * 1024 * 5)->fileSize()->value());
        $this->assertSame('10 TB', $this->numeral((1024 ** 4) * 10)->fileSize()->value());
        $this->assertSame('10 PB', $this->numeral((1024 ** 5) * 10)->fileSize()->value());
        $this->assertSame('1 ZB', $this->numeral(1024 ** 7)->fileSize()->value());
        $this->assertSame('1 YB', $this->numeral(1024 ** 8)->fileSize()->value());
        $this->assertSame('1,024 YB', $this->numeral(1024 ** 9)->fileSize()->value());
    }

    public function testToHuman()
    {
        $this->assertSame('1', $this->numeral(1)->forHumans(1)->value());
        $this->assertSame('1.00', $this->numeral(1)->forHumans(2)->value());
        $this->assertSame('10', $this->numeral(10)->forHumans(10)->value());
        $this->assertSame('100', $this->numeral(100)->forHumans(100)->value());
        $this->assertSame('1 thousand', $this->numeral(1000)->forHumans()->value());
        $this->assertSame('1.00 thousand', $this->numeral(1000)->forHumans(2)->value());
        $this->assertSame('1 thousand', $this->numeral(1000)->forHumans(maxPrecision: 2)->value());
        $this->assertSame('1 thousand', $this->numeral(1230)->forHumans()->value());
        $this->assertSame('1.2 thousand', $this->numeral(1230)->forHumans(maxPrecision: 1)->value());
        $this->assertSame('1 million', $this->numeral(1000000)->forHumans()->value());
        $this->assertSame('1 billion', $this->numeral(1000000000)->forHumans()->value());
        $this->assertSame('1 trillion', $this->numeral(1000000000000)->forHumans()->value());
        $this->assertSame('1 quadrillion', $this->numeral(1000000000000000)->forHumans()->value());
        $this->assertSame('1 thousand quadrillion', $this->numeral(1000000000000000000)->forHumans()->value());

        $this->assertSame('123', $this->numeral(123)->forHumans()->value());
        $this->assertSame('1 thousand', $this->numeral(1234)->forHumans()->value());
        $this->assertSame('1.23 thousand', $this->numeral(1234)->forHumans(2)->value());
        $this->assertSame('12 thousand', $this->numeral(12345)->forHumans()->value());
        $this->assertSame('1 million', $this->numeral(1234567)->forHumans()->value());
        $this->assertSame('1 billion', $this->numeral(1234567890)->forHumans()->value());
        $this->assertSame('1 trillion', $this->numeral(1234567890123)->forHumans()->value());
        $this->assertSame('1.23 trillion', $this->numeral(1234567890123)->forHumans(2)->value());
        $this->assertSame('1 quadrillion', $this->numeral(1234567890123456)->forHumans()->value());
        $this->assertSame('1.23 thousand quadrillion', $this->numeral(1234567890123456789)->forHumans(2)->value());
        $this->assertSame('490 thousand', $this->numeral(489939)->forHumans()->value());
        $this->assertSame('489.9390 thousand', $this->numeral(489939)->forHumans(4)->value());
        $this->assertSame('500.00000 million', $this->numeral(500000000)->forHumans(5)->value());

        $this->assertSame('1 million quadrillion', $this->numeral(1000000000000000000000)->forHumans()->value());
        $this->assertSame('1 billion quadrillion', $this->numeral(1000000000000000000000000)->forHumans()->value());
        $this->assertSame('1 trillion quadrillion', $this->numeral(1000000000000000000000000000)->forHumans()->value());
        $this->assertSame('1 quadrillion quadrillion', $this->numeral(1000000000000000000000000000000)->forHumans()->value());
        $this->assertSame('1 thousand quadrillion quadrillion', $this->numeral(1000000000000000000000000000000000)->forHumans()->value());

        $this->assertSame('0', $this->numeral(0)->forHumans()->value());
        $this->assertSame('0', $this->numeral(0.0)->forHumans()->value());
        $this->assertSame('0.00', $this->numeral(0)->forHumans(2));
        $this->assertSame('0.00', $this->numeral(0.0)->forHumans(2));
        $this->assertSame('-1', $this->numeral(-1)->forHumans()->value());
        $this->assertSame('-1.00', $this->numeral(-1)->forHumans(2));
        $this->assertSame('-10', $this->numeral(-10)->forHumans()->value());
        $this->assertSame('-100', $this->numeral(-100)->forHumans()->value());
        $this->assertSame('-1 thousand', $this->numeral(-1000)->forHumans()->value());
        $this->assertSame('-1.23 thousand', $this->numeral(-1234)->forHumans(2)->value());
        $this->assertSame('-1.2 thousand', $this->numeral(-1234)->forHumans(maxPrecision: 1)->value());
        $this->assertSame('-1 million', $this->numeral(-1000000)->forHumans()->value());
        $this->assertSame('-1 billion', $this->numeral(-1000000000)->forHumans()->value());
        $this->assertSame('-1 trillion', $this->numeral(-1000000000000)->forHumans()->value());
        $this->assertSame('-1.1 trillion', $this->numeral(-1100000000000)->forHumans(maxPrecision: 1)->value());
        $this->assertSame('-1 quadrillion', $this->numeral(-1000000000000000)->forHumans()->value());
        $this->assertSame('-1 thousand quadrillion', $this->numeral(-1000000000000000000)->forHumans()->value());
    }

    public function testSummarize()
    {
        $this->assertSame('1', $this->numeral(1)->abbreviate()->value());
        $this->assertSame('1.00', $this->numeral(1)->abbreviate(2)->value());
        $this->assertSame('10', $this->numeral(10)->abbreviate()->value());
        $this->assertSame('100', $this->numeral(100)->abbreviate()->value());
        $this->assertSame('1K', $this->numeral(1000)->abbreviate()->value());
        $this->assertSame('1.00K', $this->numeral(1000)->abbreviate(2)->value());
        $this->assertSame('1K', $this->numeral(1000)->abbreviate(maxPrecision: 2)->value());
        $this->assertSame('1K', $this->numeral(1230)->abbreviate()->value());
        $this->assertSame('1.2K', $this->numeral(1230)->abbreviate(maxPrecision: 1)->value());
        $this->assertSame('1M', $this->numeral(1000000)->abbreviate()->value());
        $this->assertSame('1B', $this->numeral(1000000000)->abbreviate()->value());
        $this->assertSame('1T', $this->numeral(1000000000000)->abbreviate()->value());
        $this->assertSame('1Q', $this->numeral(1000000000000000)->abbreviate()->value());
        $this->assertSame('1KQ', $this->numeral(1000000000000000000)->abbreviate()->value());

        $this->assertSame('123', $this->numeral(123)->abbreviate()->value());
        $this->assertSame('1K', $this->numeral(1234)->abbreviate()->value());
        $this->assertSame('1.23K', $this->numeral(1234)->abbreviate(2)->value());
        $this->assertSame('12K', $this->numeral(12345)->abbreviate()->value());
        $this->assertSame('1M', $this->numeral(1234567)->abbreviate()->value());
        $this->assertSame('1B', $this->numeral(1234567890)->abbreviate()->value());
        $this->assertSame('1T', $this->numeral(1234567890123)->abbreviate()->value());
        $this->assertSame('1.23T', $this->numeral(1234567890123)->abbreviate(2));
        $this->assertSame('1Q', $this->numeral(1234567890123456)->abbreviate()->value());
        $this->assertSame('1.23KQ', $this->numeral(1234567890123456789)->abbreviate(2)->value());
        $this->assertSame('490K', $this->numeral(489939)->abbreviate()->value());
        $this->assertSame('489.9390K', $this->numeral(489939)->abbreviate(4)->value());
        $this->assertSame('500.00000M', $this->numeral(500000000)->abbreviate(5)->value());

        $this->assertSame('1MQ', $this->numeral(1000000000000000000000)->abbreviate()->value());
        $this->assertSame('1BQ', $this->numeral(1000000000000000000000000)->abbreviate()->value());
        $this->assertSame('1TQ', $this->numeral(1000000000000000000000000000)->abbreviate()->value());
        $this->assertSame('1QQ', $this->numeral(1000000000000000000000000000000)->abbreviate()->value());
        $this->assertSame('1KQQ', $this->numeral(1000000000000000000000000000000000)->abbreviate()->value());

        $this->assertSame('0', $this->numeral(0)->abbreviate()->value());
        $this->assertSame('0', $this->numeral(0.0)->abbreviate()->value());
        $this->assertSame('0.00', $this->numeral(0)->abbreviate(2)->value());
        $this->assertSame('0.00', $this->numeral(0.0)->abbreviate(2)->value());
        $this->assertSame('-1', $this->numeral(-1)->abbreviate()->value());
        $this->assertSame('-1.00', $this->numeral(-1)->abbreviate(2)->value());
        $this->assertSame('-10', $this->numeral(-10)->abbreviate()->value());
        $this->assertSame('-100', $this->numeral(-100)->abbreviate()->value());
        $this->assertSame('-1K', $this->numeral(-1000)->abbreviate()->value());
        $this->assertSame('-1.23K', $this->numeral(-1234)->abbreviate(2)->value());
        $this->assertSame('-1.2K', $this->numeral(-1234)->abbreviate(maxPrecision: 1)->value());
        $this->assertSame('-1M', $this->numeral(-1000000)->abbreviate()->value());
        $this->assertSame('-1B', $this->numeral(-1000000000)->abbreviate()->value());
        $this->assertSame('-1T', $this->numeral(-1000000000000)->abbreviate()->value());
        $this->assertSame('-1.1T', $this->numeral(-1100000000000)->abbreviate(maxPrecision: 1)->value());
        $this->assertSame('-1Q', $this->numeral(-1000000000000000)->abbreviate()->value());
        $this->assertSame('-1KQ', $this->numeral(-1000000000000000000)->abbreviate()->value());
    }

    public function testMax()
    {
        $this->assertSame(3, $this->numeral(3)->max(2)->value());
        $this->assertSame(3, $this->numeral(3)->max(3)->value());
        $this->assertSame(3, $this->numeral(3)->max(4)->value());

        $this->assertSame(11.0, $this->numeral(11.0)->max(5.4)->value());
        $this->assertSame(11.0, $this->numeral(11.0)->max(11.0)->value());
        $this->assertSame(11.1, $this->numeral(11.0)->max(11.1)->value());
    }

    public function testMin()
    {
        $this->assertSame(3, $this->numeral(3)->min(4)->value());
        $this->assertSame(3, $this->numeral(3)->min(3)->value());
        $this->assertSame(3, $this->numeral(3)->min(2)->value());

        $this->assertSame(11.0, $this->numeral(11.0)->min(11.1)->value());
        $this->assertSame(11.0, $this->numeral(11.0)->min(11.0)->value());
        $this->assertSame(5.4, $this->numeral(11.0)->min(5.4)->value());
    }

    public function testClamp()
    {
        $this->assertSame(2, $this->numeral(1)->clamp(2, 3));
        $this->assertSame(3, $this->numeral(5)->clamp(2, 3));
        $this->assertSame(5, $this->numeral(5)->clamp(1, 10));
        $this->assertSame(4.5, $this->numeral(4.5)->clamp(1, 10));
        $this->assertSame(1, $this->numeral(-10)->clamp(1, 5));
    }

    public function testSum()
    {
        $this->assertSame(5, $this->numeral(2)->sum(3)->value());
        $this->assertSame(5.0, $this->numeral(2)->sum(3.0)->value());
        $this->assertSame(5.0, $this->numeral(2.0)->sum(3)->value());

        $this->assertSame(5, $this->numeral(-10)->sum(15)->value());
        $this->assertSame(5.0, $this->numeral(-10)->sum(15.0)->value());
        $this->assertSame(5.0, $this->numeral(-10.0)->sum(15)->value());

        $this->assertSame(5, $this->numeral(1)->sum(1, 1, 2)->value());
        $this->assertSame(5.0, $this->numeral(1)->sum(1.0, 1.0, 2.0)->value());
        $this->assertSame(5.0, $this->numeral(1.0)->sum(1, 1, 2)->value());

        $this->assertSame(5, $this->numeral(-10)->sum(15, -10, 10)->value());
    }

    public function testSubtract()
    {
        $this->assertSame(-1, $this->numeral(2)->subtract(3)->value());
        $this->assertSame(-1.0, $this->numeral(2)->subtract(3.0)->value());
        $this->assertSame(-1.0, $this->numeral(2.0)->subtract(3)->value());

        $this->assertSame(-25, $this->numeral(-10)->subtract(15)->value());
        $this->assertSame(-25.0, $this->numeral(-10)->subtract(15.0)->value());
        $this->assertSame(-25.0, $this->numeral(-10.0)->subtract(15)->value());

        $this->assertSame(10, $this->numeral(25)->subtract(15)->value());
        $this->assertSame(10.0, $this->numeral(25)->subtract(15.0)->value());
        $this->assertSame(10.0, $this->numeral(25.0)->subtract(15)->value());
    }

    public function testMultiply()
    {
        $this->assertSame(6, $this->numeral(2)->multiply(3)->value());
        $this->assertSame(6.0, $this->numeral(2)->multiply(3.0)->value());
        $this->assertSame(6.0, $this->numeral(2.0)->multiply(3)->value());

        $this->assertSame(-150, $this->numeral(-10)->multiply(15)->value());
        $this->assertSame(-150.0, $this->numeral(-10)->multiply(15.0)->value());
        $this->assertSame(-150.0, $this->numeral(-10.0)->multiply(15)->value());

        $this->assertSame(375, $this->numeral(25)->multiply(15)->value());
        $this->assertSame(375.0, $this->numeral(25)->multiply(15.0)->value());
        $this->assertSame(375.0, $this->numeral(25.0)->multiply(15)->value());

        $this->assertSame(-6, $this->numeral(6)->multiply(-1)->value());

        $this->assertSame(0, $this->numeral(0)->multiply(100)->value());
        $this->assertSame(0, $this->numeral(100)->multiply(0)->value());
    }

    public function testDivide()
    {
        $this->assertSame(2, $this->numeral(6)->divide(3)->value());
        $this->assertSame(2.0, $this->numeral(6)->divide(3.0)->value());
        $this->assertSame(2.0, $this->numeral(6.0)->divide(3)->value());

        $this->assertSame(-10, $this->numeral(-150)->divide(15)->value());
        $this->assertSame(-10.0, $this->numeral(-150)->divide(15.0)->value());
        $this->assertSame(-10.0, $this->numeral(-150.0)->divide(15)->value());

        $this->assertSame(25, $this->numeral(375)->divide(15)->value());
        $this->assertSame(25.0, $this->numeral(375)->divide(15.0)->value());
        $this->assertSame(25.0, $this->numeral(375.0)->divide(15)->value());

        $this->assertSame(-6, $this->numeral(6)->divide(-1)->value());
    }

    public function testEquals()
    {
        $this->assertTrue($this->numeral(3)->equals(3));
        $this->assertTrue($this->numeral(3)->equals(3.0));
        $this->assertTrue($this->numeral(3.0)->equals(3));

        $this->assertFalse($this->numeral(3)->equals(4));
        $this->assertFalse($this->numeral(3)->equals(3.1));
        $this->assertFalse($this->numeral(3.1)->equals(3));
    }

    public function testNearlyEquals()
    {
        $this->assertTrue($this->numeral(3)->nearlyEquals(3));
        $this->assertTrue($this->numeral(3)->nearlyEquals(3.0));
        $this->assertTrue($this->numeral(3.0)->nearlyEquals(3));

        $this->assertTrue($this->numeral(3)->nearlyEquals(3.1, 0.1));
        $this->assertTrue($this->numeral(3.1)->nearlyEquals(3, 0.1));

        $this->assertFalse($this->numeral(3)->nearlyEquals(4));
        $this->assertFalse($this->numeral(3)->nearlyEquals(3.2));
        $this->assertFalse($this->numeral(3.2)->nearlyEquals(3));
    }

    public function testGreaterThan()
    {
        $this->assertTrue($this->numeral(3)->greaterThan(2));
        $this->assertTrue($this->numeral(3)->greaterThan(2.9));
        $this->assertTrue($this->numeral(3.1)->greaterThan(3));
        $this->assertTrue($this->numeral(3)->greaterThan(-1));
        $this->assertTrue($this->numeral(3)->greaterThan(-1.0));
        $this->assertTrue($this->numeral(3.1)->greaterThan(-1));
        $this->assertTrue($this->numeral(3.1)->greaterThan(-1.0));
        $this->assertTrue($this->numeral(-3)->greaterThan(-3));
        $this->assertTrue($this->numeral(-3)->greaterThan(-5));
        $this->assertTrue($this->numeral(-3)->greaterThan(-5.0));
        $this->assertTrue($this->numeral(-3.1)->greaterThan(-5));
        $this->assertTrue($this->numeral(-3.1)->greaterThan(-5.0));

        $this->assertFalse($this->numeral(3)->greaterThan(3));
        $this->assertFalse($this->numeral(3)->greaterThan(3.1));
        $this->assertFalse($this->numeral(3.1)->greaterThan(3.1));
        $this->assertFalse($this->numeral(3)->greaterThan(4));
        $this->assertFalse($this->numeral(3)->greaterThan(4.0));
        $this->assertFalse($this->numeral(3.1)->greaterThan(4));
        $this->assertFalse($this->numeral(3.1)->greaterThan(4.0));
        $this->assertFalse($this->numeral(-3)->greaterThan(3));
        $this->assertFalse($this->numeral(-3)->greaterThan(3.0));
        $this->assertFalse($this->numeral(-3.1)->greaterThan(3));
        $this->assertFalse($this->numeral(-3.1)->greaterThan(3.0));
    }

    public function testGraterThanOrEquals()
    {
        $this->assertTrue($this->numeral(3)->greaterThanOrEquals(2));
        $this->assertTrue($this->numeral(3)->greaterThanOrEquals(2.9));
        $this->assertTrue($this->numeral(3.1)->greaterThanOrEquals(3));
        $this->assertTrue($this->numeral(3)->greaterThanOrEquals(3));
        $this->assertTrue($this->numeral(3)->greaterThanOrEquals(-1));
        $this->assertTrue($this->numeral(3)->greaterThanOrEquals(-1.0));
        $this->assertTrue($this->numeral(3.1)->greaterThanOrEquals(-1));
        $this->assertTrue($this->numeral(3.1)->greaterThanOrEquals(-1.0));
        $this->assertTrue($this->numeral(-3)->greaterThanOrEquals(-3));
        $this->assertTrue($this->numeral(-3)->greaterThanOrEquals(-5));
        $this->assertTrue($this->numeral(-3)->greaterThanOrEquals(-5.0));
        $this->assertTrue($this->numeral(-3.1)->greaterThanOrEquals(-5));
        $this->assertTrue($this->numeral(-3.1)->greaterThanOrEquals(-5.0));

        $this->assertFalse($this->numeral(3)->greaterThanOrEquals(4));
        $this->assertFalse($this->numeral(3)->greaterThanOrEquals(4.0));
        $this->assertFalse($this->numeral(3.1)->greaterThanOrEquals(4));
        $this->assertFalse($this->numeral(3.1)->greaterThanOrEquals(4.0));
        $this->assertFalse($this->numeral(-3)->greaterThanOrEquals(3));
        $this->assertFalse($this->numeral(-3)->greaterThanOrEquals(3.0));
        $this->assertFalse($this->numeral(-3.1)->greaterThanOrEquals(3));
        $this->assertFalse($this->numeral(-3.1)->greaterThanOrEquals(3.0));
    }

    public function testLessThan()
    {
        $this->assertTrue($this->numeral(2)->lessThan(3));
        $this->assertTrue($this->numeral(2.9)->lessThan(3));
        $this->assertTrue($this->numeral(3)->lessThan(3.1));
        $this->assertTrue($this->numeral(-1)->lessThan(3));
        $this->assertTrue($this->numeral(-1.0)->lessThan(3));
        $this->assertTrue($this->numeral(-1)->lessThan(3.1));
        $this->assertTrue($this->numeral(-1.0)->lessThan(3.1));
        $this->assertTrue($this->numeral(-3)->lessThan(-2));
        $this->assertTrue($this->numeral(-3)->lessThan(3));
        $this->assertTrue($this->numeral(-3)->lessThan(3.0));
        $this->assertTrue($this->numeral(-3.1)->lessThan(3));
        $this->assertTrue($this->numeral(-3.1)->lessThan(3.0));

        $this->assertFalse($this->numeral(3)->lessThan(3));
        $this->assertFalse($this->numeral(3.1)->lessThan(3));
        $this->assertFalse($this->numeral(3.1)->lessThan(3.1));
        $this->assertFalse($this->numeral(4)->lessThan(3));
        $this->assertFalse($this->numeral(4.0)->lessThan(3));
        $this->assertFalse($this->numeral(4)->lessThan(3.1));
        $this->assertFalse($this->numeral(4.0)->lessThan(3.1));
        $this->assertFalse($this->numeral(3)->lessThan(-3));
        $this->assertFalse($this->numeral(3.0)->lessThan(-3));
        $this->assertFalse($this->numeral(3)->lessThan(-3.1));
        $this->assertFalse($this->numeral(3.0)->lessThan(-3.1));
    }

    public function testLessThanOrEquals()
    {
        $this->assertTrue($this->numeral(2)->lessThanOrEquals(3));
        $this->assertTrue($this->numeral(2.9)->lessThanOrEquals(3));
        $this->assertTrue($this->numeral(3)->lessThanOrEquals(3.1));
        $this->assertTrue($this->numeral(3)->lessThanOrEquals(3));
        $this->assertTrue($this->numeral(-1)->lessThanOrEquals(3));
        $this->assertTrue($this->numeral(-1.0)->lessThanOrEquals(3));
        $this->assertTrue($this->numeral(-1)->lessThanOrEquals(3.1));
        $this->assertTrue($this->numeral(-1.0)->lessThanOrEquals(3.1));
        $this->assertTrue($this->numeral(-3)->lessThanOrEquals(-2));
        $this->assertTrue($this->numeral(-3)->lessThanOrEquals(3));
        $this->assertTrue($this->numeral(-3)->lessThanOrEquals(3.0));
        $this->assertTrue($this->numeral(-3.1)->lessThanOrEquals(3));
        $this->assertTrue($this->numeral(-3.1)->lessThanOrEquals(3.0));
        $this->assertTrue($this->numeral(-3)->lessThanOrEquals(-3));

        $this->assertFalse($this->numeral(4)->lessThanOrEquals(3));
        $this->assertFalse($this->numeral(4.0)->lessThanOrEquals(3));
        $this->assertFalse($this->numeral(4)->lessThanOrEquals(3.1));
        $this->assertFalse($this->numeral(4.0)->lessThanOrEquals(3.1));
        $this->assertFalse($this->numeral(3)->lessThanOrEquals(-3));
        $this->assertFalse($this->numeral(3.0)->lessThanOrEquals(-3));
        $this->assertFalse($this->numeral(3)->lessThanOrEquals(-3.1));
        $this->assertFalse($this->numeral(3.0)->lessThanOrEquals(-3.1));
    }

    public function testBetween()
    {
        $this->assertTrue($this->numeral(3)->between(2, 4));
        $this->assertTrue($this->numeral(3)->between(3, 4));
        $this->assertTrue($this->numeral(3)->between(2, 3));
        $this->assertTrue($this->numeral(3)->between(3, 3));
        $this->assertTrue($this->numeral(-3)->between(-4, -2));
        $this->assertTrue($this->numeral(-3)->between(-3, -2));
        $this->assertTrue($this->numeral(-3)->between(-4, -3));
        $this->assertTrue($this->numeral(-3)->between(-3, -3));
        $this->assertTrue($this->numeral(3)->between(-4, 4));
        $this->assertTrue($this->numeral(3)->between(4, -4));
        $this->assertTrue($this->numeral(-3)->between(-4, 4));
        $this->assertTrue($this->numeral(-3)->between(4, -4));

        $this->assertFalse($this->numeral(3)->between(4, 5));
        $this->assertFalse($this->numeral(3)->between(1, 2));
        $this->assertFalse($this->numeral(-3)->between(-2, -1));
        $this->assertFalse($this->numeral(-3)->between(-1, -2));
        $this->assertFalse($this->numeral(3)->between(4, 2));
        $this->assertFalse($this->numeral(3)->between(2, 4));
        $this->assertFalse($this->numeral(-3)->between(4, 2));
        $this->assertFalse($this->numeral(-3)->between(2, 4));
    }

    public function testIncrement()
    {
        $this->assertSame(4, $this->numeral(3)->increment()->value());
        $this->assertSame(4.0, $this->numeral(3.0)->increment()->value());
        $this->assertSame(4, $this->numeral(3)->increment(1)->value());
        $this->assertSame(4.0, $this->numeral(3)->increment(1.0)->value());
        $this->assertSame(4.0, $this->numeral(3.0)->increment(1)->value());
        $this->assertSame(5, $this->numeral(3)->increment(2)->value());
        $this->assertSame(5.0, $this->numeral(3)->increment(2.0)->value());
        $this->assertSame(5.0, $this->numeral(3.0)->increment(2)->value());
    }

    public function testDecrement()
    {
        $this->assertSame(2, $this->numeral(3)->decrement()->value());
        $this->assertSame(2.0, $this->numeral(3.0)->decrement()->value());
        $this->assertSame(2, $this->numeral(3)->decrement(1)->value());
        $this->assertSame(2.0, $this->numeral(3)->decrement(1.0)->value());
        $this->assertSame(2.0, $this->numeral(3.0)->decrement(1)->value());
        $this->assertSame(1, $this->numeral(3)->decrement(2)->value());
        $this->assertSame(1.0, $this->numeral(3)->decrement(2.0)->value());
        $this->assertSame(1.0, $this->numeral(3.0)->decrement(2)->value());
    }

    public function testAbs()
    {
        $this->assertSame(3, $this->numeral(3)->abs()->value());
        $this->assertSame(3.0, $this->numeral(3.0)->abs()->value());
        $this->assertSame(3, $this->numeral(-3)->abs()->value());
        $this->assertSame(3.0, $this->numeral(-3.0)->abs()->value());
    }

    public function testCeil()
    {
        $this->assertSame(4, $this->numeral(3.1)->ceil()->value());
    }

    public function testFloor()
    {
        $this->assertSame(3, $this->numeral(3.9)->floor()->value());
    }

    public function testRound()
    {
        $this->assertSame(3, $this->numeral(3.1)->round()->value());
        $this->assertSame(4, $this->numeral(3.9)->round()->value());
        $this->assertSame(3.1, $this->numeral(3.14159)->round(1)->value());
        $this->assertSame(3.14, $this->numeral(3.14159)->round(2)->value());
        $this->assertSame(3.142, $this->numeral(3.14159)->round(3)->value());
        $this->assertSame(3.1416, $this->numeral(3.14159)->round(4)->value());
        $this->assertSame(3.14159, $this->numeral(3.14159)->round(5)->value());
    }

    public function testLen()
    {
        $this->assertSame(1, $this->numeral(3)->len());
        $this->assertSame(1, $this->numeral(3.0)->len());
        $this->assertSame(2, $this->numeral(30)->len());
        $this->assertSame(3, $this->numeral(30.0)->len());
        $this->assertSame(3, $this->numeral(300)->len());
        $this->assertSame(4, $this->numeral(300.0)->len());
    }

    public function testSqrt()
    {
        $this->assertSame(3, $this->numeral(9)->sqrt()->value());
        $this->assertSame(3.0, $this->numeral(9.0)->sqrt()->value());
    }

    public function testCbrt()
    {
        $this->assertSame(3.0, $this->numeral(27)->cbrt()->value());
        $this->assertSame(3.0, $this->numeral(27.0)->cbrt()->value());
    }

    public function testPow()
    {
        $this->assertSame(9, $this->numeral(3)->pow(2)->value());
        $this->assertSame(9.0, $this->numeral(3.0)->pow(2)->value());
    }

    public function testMod()
    {
        $this->assertSame(1, $this->numeral(10)->mod(3)->value());
        $this->assertSame(1.0, $this->numeral(10.0)->mod(3)->value());
    }

    public function testLog()
    {
        $this->assertSame(4.6051701859881, $this->numeral(100)->log()->value());
        $this->assertSame(4.6051701859881, $this->numeral(100.0)->log()->value());
    }

    public function testLog10()
    {
        $this->assertSame(2.0, $this->numeral(100)->log10()->value());
        $this->assertSame(2.0, $this->numeral(100.0)->log10()->value());
    }

    public function testLog1p()
    {
        $this->assertSame(4.6151205168413, $this->numeral(100)->log1p()->value());
        $this->assertSame(4.6151205168413, $this->numeral(100.0)->log1p()->value());
    }

    public function testExp()
    {
        $this->assertSame(20.085536923188, $this->numeral(3)->exp()->value());
        $this->assertSame(20.085536923188, $this->numeral(3.0)->exp()->value());
    }

    public function testExpm1()
    {
        $this->assertSame(19.085536923188, $this->numeral(3)->expm1()->value());
        $this->assertSame(19.085536923188, $this->numeral(3.0)->expm1()->value());
    }

    public function testCos()
    {
        $this->assertSame(-0.98999249660045, $this->numeral(3)->cos()->value());
        $this->assertSame(-0.98999249660045, $this->numeral(3.0)->cos()->value());
    }

    public function testSin()
    {
        $this->assertSame(0.14112000805987, $this->numeral(3)->sin()->value());
        $this->assertSame(0.14112000805987, $this->numeral(3.0)->sin()->value());
    }

    public function testTan()
    {
        $this->assertSame(-0.14254654307428, $this->numeral(3)->tan()->value());
        $this->assertSame(-0.14254654307428, $this->numeral(3.0)->tan()->value());
    }

    public function testAcos()
    {
        $this->assertSame(0.14159265358979, $this->numeral(0.1)->acos()->value());
        $this->assertSame(0.14159265358979, $this->numeral(0.1)->acos()->value());
    }

    public function testAsin()
    {
        $this->assertSame(0.10016742116156, $this->numeral(0.1)->asin()->value());
        $this->assertSame(0.10016742116156, $this->numeral(0.1)->asin()->value());
    }

    public function testAtan()
    {
        $this->assertSame(0.099668652491162, $this->numeral(0.1)->atan()->value());
        $this->assertSame(0.099668652491162, $this->numeral(0.1)->atan()->value());
    }

    public function testAtan2()
    {
        $this->assertSame(0.24497866312686, $this->numeral(0.1)->atan2(0.2)->value());
        $this->assertSame(0.24497866312686, $this->numeral(0.1)->atan2(0.2)->value());
    }

    public function testCosh()
    {
        $this->assertSame(1.600286857702, $this->numeral(3)->cosh()->value());
        $this->assertSame(1.600286857702, $this->numeral(3.0)->cosh()->value());
    }

    public function testSinh()
    {
        $this->assertSame(10.01787492741, $this->numeral(3)->sinh()->value());
        $this->assertSame(10.01787492741, $this->numeral(3.0)->sinh()->value());
    }

    public function testTanh()
    {
        $this->assertSame(0.99505475368673, $this->numeral(3)->tanh()->value());
        $this->assertSame(0.99505475368673, $this->numeral(3.0)->tanh()->value());
    }

    public function testAcosh()
    {
        $this->assertSame(1.7627471740391, $this->numeral(3)->acosh()->value());
        $this->assertSame(1.7627471740391, $this->numeral(3.0)->acosh()->value());
    }

    public function testAsinh()
    {
        $this->assertSame(1.8184464592321, $this->numeral(3)->asinh()->value());
        $this->assertSame(1.8184464592321, $this->numeral(3.0)->asinh()->value());
    }

    public function testAtanh()
    {
        $this->assertSame(1.0986122886681, $this->numeral(0.8)->atanh()->value());
        $this->assertSame(1.0986122886681, $this->numeral(0.8)->atanh()->value());
    }

    public function testGreatestCommonDivisor()
    {
        $this->assertSame(1, $this->numeral(3)->gcd(2)->value());
        $this->assertSame(1, $this->numeral(3.0)->gcd(2)->value());
        $this->assertSame(1, $this->numeral(3)->gcd(2.0)->value());
        $this->assertSame(1, $this->numeral(3.0)->gcd(2.0)->value());
        $this->assertSame(1, $this->numeral(1)->gcd(1)->value());
        $this->assertSame(1, $this->numeral(1)->gcd(2)->value());
        $this->assertSame(1, $this->numeral(2)->gcd(1)->value());
        $this->assertSame(2, $this->numeral(2)->gcd(2)->value());
        $this->assertSame(2, $this->numeral(2)->gcd(4)->value());
        $this->assertSame(2, $this->numeral(4)->gcd(2)->value());
        $this->assertSame(2, $this->numeral(4)->gcd(6)->value());
        $this->assertSame(2, $this->numeral(6)->gcd(4)->value());
        $this->assertSame(3, $this->numeral(3)->gcd(3)->value());
        $this->assertSame(3, $this->numeral(3)->gcd(6)->value());
        $this->assertSame(3, $this->numeral(6)->gcd(3)->value());
        $this->assertSame(3, $this->numeral(6)->gcd(9)->value());
        $this->assertSame(3, $this->numeral(9)->gcd(6)->value());
        $this->assertSame(3, $this->numeral(9)->gcd(12)->value());
        $this->assertSame(3, $this->numeral(12)->gcd(9)->value());
        $this->assertSame(3, $this->numeral(12)->gcd(15)->value());
        $this->assertSame(3, $this->numeral(15)->gcd(12)->value());
        $this->assertSame(3, $this->numeral(15)->gcd(18)->value());
        $this->assertSame(3, $this->numeral(18)->gcd(15)->value());
    }

    public function testLowestCommonMultiplier()
    {
        $this->assertSame(6, $this->numeral(3)->lcm(2)->value());
        $this->assertSame(6, $this->numeral(3.0)->lcm(2)->value());
        $this->assertSame(6, $this->numeral(3)->lcm(2.0)->value());
        $this->assertSame(6, $this->numeral(3.0)->lcm(2.0)->value());
        $this->assertSame(1, $this->numeral(1)->lcm(1)->value());
        $this->assertSame(2, $this->numeral(1)->lcm(2)->value());
        $this->assertSame(2, $this->numeral(2)->lcm(1)->value());
        $this->assertSame(2, $this->numeral(2)->lcm(2)->value());
        $this->assertSame(4, $this->numeral(2)->lcm(4)->value());
        $this->assertSame(4, $this->numeral(4)->lcm(2)->value());
        $this->assertSame(6, $this->numeral(2)->lcm(3)->value());
        $this->assertSame(6, $this->numeral(3)->lcm(2)->value());
        $this->assertSame(6, $this->numeral(3)->lcm(3)->value());
        $this->assertSame(6, $this->numeral(3)->lcm(6)->value());
        $this->assertSame(6, $this->numeral(6)->lcm(3)->value());
        $this->assertSame(6, $this->numeral(6)->lcm(6)->value());
        $this->assertSame(12, $this->numeral(3)->lcm(4)->value());
        $this->assertSame(12, $this->numeral(4)->lcm(3)->value());
        $this->assertSame(12, $this->numeral(4)->lcm(4)->value());
        $this->assertSame(12, $this->numeral(4)->lcm(6)->value());
        $this->assertSame(12, $this->numeral(6)->lcm(4)->value());
        $this->assertSame(12, $this->numeral(6)->lcm(6)->value());
        $this->assertSame(15, $this->numeral(3)->lcm(5)->value());
        $this->assertSame(15, $this->numeral(5)->lcm(3)->value());
        $this->assertSame(15, $this->numeral(5)->lcm(5)->value());
    }

    public function testFactorial()
    {
        // repeat the following code for each test case
        $this->assertSame(1, $this->numeral(0)->factorial());
        $this->assertSame(1, $this->numeral(1)->factorial());
        $this->assertSame(2, $this->numeral(2)->factorial());
        $this->assertSame(6, $this->numeral(3)->factorial());
        $this->assertSame(24, $this->numeral(4)->factorial());
        $this->assertSame(120, $this->numeral(5)->factorial());
        $this->assertSame(720, $this->numeral(6)->factorial());
        $this->assertSame(5040, $this->numeral(7)->factorial());
        $this->assertSame(40320, $this->numeral(8)->factorial());
        $this->assertSame(362880, $this->numeral(9)->factorial());
        $this->assertSame(3628800, $this->numeral(10)->factorial());
    }

    public function testCopySign()
    {
        $this->assertSame(3, $this->numeral(3)->copySign(2)->value());
        $this->assertSame(3.0, $this->numeral(3.0)->copySign(2)->value());
        $this->assertSame(-3, $this->numeral(3)->copySign(-2)->value());
        $this->assertSame(-3.0, $this->numeral(3.0)->copySign(-2)->value());
        $this->assertSame(3, $this->numeral(-3)->copySign(2)->value());
        $this->assertSame(3.0, $this->numeral(-3.0)->copySign(2)->value());
        $this->assertSame(-3, $this->numeral(-3)->copySign(-2)->value());
        $this->assertSame(-3.0, $this->numeral(-3.0)->copySign(-2)->value());
        $this->assertSame(3, $this->numeral(3)->copySign(0)->value());
    }

    public function testValue()
    {
        $this->assertSame(3, $this->numeral(3)->value());
        $this->assertSame(11.0, $this->numeral(11.0)->value());
    }

    public function testToString()
    {
        $this->assertSame('3', $this->numeral(3)->toString());
        $this->assertSame('11.0', $this->numeral(11.0)->toString());
    }

    public function testToInt()
    {
        $this->assertSame(3, $this->numeral(3)->toInt());
        $this->assertSame(11, $this->numeral(11.0)->toInt());
        $this->assertSame(-3, $this->numeral(-3)->toInt());
        $this->assertSame(-11, $this->numeral(-11.0)->toInt());
    }
}
