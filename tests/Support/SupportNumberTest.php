<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Number;
use PHPUnit\Framework\TestCase;

class SupportNumberTest extends TestCase
{
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
