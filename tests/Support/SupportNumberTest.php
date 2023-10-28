<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Number;
use PHPUnit\Framework\TestCase;

class SupportNumberTest extends TestCase
{
    public function testBytesToHuman()
    {
        $this->assertSame('0 B', Number::bytesToHuman(0));
        $this->assertSame('1 B', Number::bytesToHuman(1));
        $this->assertSame('1 KB', Number::bytesToHuman(1024));
        $this->assertSame('2 KB', Number::bytesToHuman(2048));
        $this->assertSame('1.23 KB', Number::bytesToHuman(1264));
        $this->assertSame('1.234 KB', Number::bytesToHuman(1264, 3));
        $this->assertSame('5 GB', Number::bytesToHuman(1024 * 1024 * 1024 * 5));
        $this->assertSame('10 TB', Number::bytesToHuman((1024 ** 4) * 10));
        $this->assertSame('10 PB', Number::bytesToHuman((1024 ** 5) * 10));
        $this->assertSame('1 ZB', Number::bytesToHuman(1024 ** 7));
        $this->assertSame('1 YB', Number::bytesToHuman(1024 ** 8));
        $this->assertSame('1024 YB', Number::bytesToHuman(1024 ** 9));
    }
}
