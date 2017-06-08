<?php

namespace Illuminate\Tests\Support;

use Exception;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\StrictFluent;

class SupportStrictFluentTest extends TestCase
{
    public function testExceptionIsThrownWhenInvalidMethodIsCalled()
    {
        $fluent = new StrictFluent(['foo' => 1]);

        $this->expectException(Exception::class);

        (function ($f) {
            $f->bar('bar');
        })($fluent);
    }

    public function testExceptionIsThrownWhenInvalidKeyIsAccessed()
    {
        $fluent = new StrictFluent(['foo' => 1]);

        $this->expectException(Exception::class);

        (function ($f) {
            $f->bar = 'bar';
        })($fluent);
    }

    public function testParametersAreAvailableAfterSetting()
    {
        $fluent = new StrictFluent(['foo' => 1, 'bar' => 2]);

        (function ($f) {
            $f->foo('foo');
        })($fluent);

        $this->assertEquals('foo', $fluent->foo);
        $this->assertEquals(2, $fluent->bar);
    }

    public function testCanApplyClosureToItself()
    {
        $fluent = new StrictFluent(['foo' => 1, 'bar' => 2]);

        $fluent->applyClosure(function ($f) {
            $f->foo('foo');
        });

        $this->assertEquals('foo', $fluent->foo);
        $this->assertEquals(2, $fluent->bar);
    }

    public function testCanInitializeItselfWithArrayOfNulls()
    {
        $fluent = StrictFluent::withNullArray(['foo', 'bar']);

        $fluent->applyClosure(function ($f) {
            $f->foo('foo');
        });

        $this->assertEquals('foo', $fluent->foo);
        $this->assertNull($fluent->bar);
    }
}
