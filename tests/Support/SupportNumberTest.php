<?php

namespace Illuminate\Tests\Support;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;
use Mockery;
use PHPUnit\Framework\TestCase;

class SupportNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        App::swap(new Container);
        App::shouldReceive('getLocale')->andReturn('en');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        App::clearResolvedInstances();
        App::swap(new Container);

        parent::tearDown();
    }

    public function testToHuman()
    {
        $this->needsIntlExtension();

        $this->assertSame('zero', Number::toHuman(0));
        $this->assertSame('one', Number::toHuman(1));
        $this->assertSame('ten', Number::toHuman(10));
        $this->assertSame('twenty-five', Number::toHuman(25));
        $this->assertSame('one hundred', Number::toHuman(100));
        $this->assertSame('one hundred thousand', Number::toHuman(100000));
        $this->assertSame('one hundred twenty-three million four hundred fifty-six thousand seven hundred eighty-nine', Number::toHuman(123456789));

        $this->assertSame('one billion', Number::toHuman(1000000000));
        $this->assertSame('one trillion', Number::toHuman(1000000000000));
        $this->assertSame('one quadrillion', Number::toHuman(1000000000000000));
        $this->assertSame('1,000,000,000,000,000,000', Number::toHuman(1000000000000000000));

        $this->assertSame('minus one', Number::toHuman(-1));
        $this->assertSame('minus ten', Number::toHuman(-10));
        $this->assertSame('minus twenty-five', Number::toHuman(-25));

        $this->assertSame('zero point two', Number::toHuman(0.2));
        $this->assertSame('one point two three', Number::toHuman(1.23));
        $this->assertSame('minus one point two three', Number::toHuman(-1.23));
        $this->assertSame('one hundred twenty-three point four five six', Number::toHuman(123.456));
    }

    public function testToHumanWithDifferentLocale()
    {
        $this->needsIntlExtension();

        $this->assertSame('cent vingt-trois', Number::toHuman(123, 'fr'));

        $this->assertSame('ein­hundert­drei­und­zwanzig', Number::toHuman(123, 'de'));

        $this->assertSame('ett­hundra­tjugo­tre', Number::toHuman(123, 'sv'));
    }

    public function testToCurrency()
    {
        $this->needsIntlExtension();

        $this->assertSame('$0.00', Number::toCurrency(0));
        $this->assertSame('$1.00', Number::toCurrency(1));
        $this->assertSame('$10.00', Number::toCurrency(10));

        $this->assertSame('€0.00', Number::toCurrency(0, 'EUR'));
        $this->assertSame('€1.00', Number::toCurrency(1, 'EUR'));
        $this->assertSame('€10.00', Number::toCurrency(10, 'EUR'));

        $this->assertSame('-$5.00', Number::toCurrency(-5));
        $this->assertSame('$5.00', Number::toCurrency(5.00));
        $this->assertSame('$5.32', Number::toCurrency(5.325));
    }

    public function testToCurrencyWithDifferentLocale()
    {
        $this->needsIntlExtension();

        $this->assertSame('1,00 €', Number::toCurrency(1, 'EUR', 'de'));
        $this->assertSame('1,00 $', Number::toCurrency(1, 'USD', 'de'));
        $this->assertSame('1,00 £', Number::toCurrency(1, 'GBP', 'de'));

        $this->assertSame('123.456.789,12 $', Number::toCurrency(123456789.12345, 'USD', 'de'));
        $this->assertSame('123.456.789,12 €', Number::toCurrency(123456789.12345, 'EUR', 'de'));
        $this->assertSame('1 234,56 $US', Number::toCurrency(1234.56, 'USD', 'fr'));
    }

    public function testBytesToHuman()
    {
        $this->assertSame('0 B', Number::bytesToHuman(0));
        $this->assertSame('1 B', Number::bytesToHuman(1));
        $this->assertSame('1 kB', Number::bytesToHuman(1024));
        $this->assertSame('2 kB', Number::bytesToHuman(2048));
        $this->assertSame('1.23 kB', Number::bytesToHuman(1264));
        $this->assertSame('1.234 kB', Number::bytesToHuman(1264, 3));
        $this->assertSame('5 GB', Number::bytesToHuman(1024 * 1024 * 1024 * 5));
        $this->assertSame('10 TB', Number::bytesToHuman((1024 ** 4) * 10));
        $this->assertSame('10 PB', Number::bytesToHuman((1024 ** 5) * 10));
        $this->assertSame('1 ZB', Number::bytesToHuman(1024 ** 7));
        $this->assertSame('1 YB', Number::bytesToHuman(1024 ** 8));
        $this->assertSame('1024 YB', Number::bytesToHuman(1024 ** 9));
    }

    protected function needsIntlExtension()
    {
        if (! extension_loaded('intl')) {
            $this->markTestSkipped('The intl extension is not installed. Please install the extension to enable '.__CLASS__);
        }
    }
}
