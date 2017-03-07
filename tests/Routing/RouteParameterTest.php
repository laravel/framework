<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Routing\RouteParameter;
use PHPUnit\Framework\TestCase;

class RouteParameterTest extends TestCase
{
    public function testParsingName()
    {
        $parameter = new RouteParameter('baz');
        $this->assertEquals('baz', $parameter->name());

        $parameter = new RouteParameter('bar:id');
        $this->assertEquals('bar', $parameter->name());

        $parameter = new RouteParameter('foo:slug');
        $this->assertEquals('foo', $parameter->name());
    }

    public function testParsingKey()
    {
        $parameter = new RouteParameter('bam');
        $this->assertNull($parameter->key());

        $parameter = new RouteParameter('baz:id');
        $this->assertEquals('id', $parameter->key());

        $parameter = new RouteParameter('foo:slug');
        $this->assertEquals('slug', $parameter->key());
    }

    public function testValue()
    {
        $parameter = new RouteParameter('foo');
        $this->assertNull($parameter->value());

        $parameter = new RouteParameter('bar', 1);
        $this->assertEquals(1, $parameter->value());
    }
}