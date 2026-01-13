<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Fluent;
use Illuminate\Support\Wrap;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class WrapTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCallsOriginalObject()
    {
        $object = m::mock(Fluent::class);
        $object->expects('get')->with('foo')->andReturn('bar');

        $wrap = Wrap::instance($object);

        $this->assertSame('bar', $wrap->get('foo'));
    }

    public function testCallsCapturedMethod()
    {
        $object = m::mock(Fluent::class);
        $object->expects('get')->never();

        $wrap = Wrap::instance($object);
        $wrap->macro('get', fn ($name) => $name . 'baz');

        $this->assertSame('foobaz', $wrap->get('foo'));
    }

    public function testCallsNonExistingCapturedMethod()
    {
        $object = m::mock(Fluent::class);
        $object->expects('invalid')->never();

        $wrap = Wrap::instance($object);
        $wrap->macro('invalid', fn ($name) => $name . 'baz');

        $this->assertSame('foobaz', $wrap->invalid('foo'));
    }

    public function testPassesThroughProperties()
    {
        $object = new Fluent(['foo' => 'bar']);

        $wrap = Wrap::instance($object);

        $this->assertSame('bar', $wrap->foo);
        $this->assertTrue(isset($wrap->foo));

        unset($wrap->foo);

        $this->assertNull($wrap->foo);
        $this->assertFalse(isset($wrap->foo));

        $wrap->foo = 'baz';

        $this->assertSame('baz', $wrap->foo);
    }
}
