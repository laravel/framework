<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Setter;
use PHPUnit\Framework\TestCase;

class SupportSetterTest extends TestCase
{
    public function testErrorIsThrownWhenInvalidMethodIsCalled()
    {
        $setter = new Setter(['foo']);

        $this->expectException(\Error::class);

        (function ($r) {
            $r->bar('bar');
        })($setter);
    }

    public function testTypeErrorIsThrownWhenNoArgumentWereProvided()
    {
        $setter = new Setter(['foo']);

        $this->expectException(\TypeError::class);

        (function ($r) {
            $r->foo();
        })($setter);
    }

    public function testParametersAreAvailableAfterSetting()
    {
        $setter = new Setter(['foo', 'bar']);

        (function ($r) {
            $r->foo('foo')
              ->bar('bar');
        })($setter);

        $this->assertEquals('foo', $setter->retrieve()['foo']);
        $this->assertEquals('bar', $setter->retrieve()['bar']);
    }

    public function testNotSetParametersAreNullByDefault()
    {
        $setter = new Setter(['foo', 'bar']);

        (function ($r) {
            $r->foo('foo');
        })($setter);

        $this->assertEquals('foo', $setter->retrieve()['foo']);
        $this->assertNull($setter->retrieve()['bar']);
    }
}
