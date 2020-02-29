<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Stringable;
use PHPUnit\Framework\TestCase;

class SupportStringableTest extends TestCase
{
    public function testMatch()
    {
        $string = new Stringable('foo bar');

        $this->assertEquals('bar', $string->match('/bar/'));
        $this->assertEquals('bar', $string->match('/foo (.*)/'));
        $this->assertTrue($string->match('/nothing/')->isEmpty());

        $string = new Stringable('bar foo bar');

        $this->assertEquals(['bar', 'bar'], $string->matchAll('/bar/')->all());

        $string = new Stringable('bar fun bar fly');

        $this->assertEquals(['un', 'ly'], $string->matchAll('/f(\w*)/')->all());
        $this->assertTrue($string->matchAll('/nothing/')->isEmpty());

        $string = new Stringable('  bar  ');

        $this->assertEquals('bar', $string->trim());
    }
}
