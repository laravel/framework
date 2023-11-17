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

    public function testNumberable()
    {
        $this->assertSame(0, $this->numberable()->value());
        $this->assertSame(1, $this->numberable(1)->value());
        $this->assertSame(1.1, $this->numberable(1.1)->value());
    }

    public function testMagicGet()
    {
        $this->assertSame(0, $this->numberable()->value);
        $this->assertSame(1, $this->numberable(1)->value);
        $this->assertSame(1.1, $this->numberable(1.1)->value);
    }

    public function testStringCast()
    {
        $this->assertSame('0', (string) $this->numberable());
        $this->assertSame('1', (string) $this->numberable(1));
        $this->assertSame('1.1', (string) $this->numberable(1.1));
    }
}
