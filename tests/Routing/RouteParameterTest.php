<?php

namespace Illuminate\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Illuminate\Routing\RouteParameter;

class RouteParameterTest extends TestCase
{
    public function testParsingName()
    {
        $parameter = new RouteParameter('baz');
        $this->assertSame('baz', $parameter->name());

        $parameter = new RouteParameter('bar:id');
        $this->assertSame('bar', $parameter->name());

        $parameter = new RouteParameter('foo:slug');
        $this->assertSame('foo', $parameter->name());
    }

    public function testParsingKey()
    {
        $parameter = new RouteParameter('bam');
        $this->assertNull($parameter->key());

        $parameter = new RouteParameter('baz:id');
        $this->assertSame('id', $parameter->key());

        $parameter = new RouteParameter('foo:slug');
        $this->assertSame('slug', $parameter->key());
    }

    public function testValue()
    {
        $parameter = new RouteParameter('foo');
        $this->assertNull($parameter->value());

        $parameter = new RouteParameter('bar', 1);
        $this->assertSame(1, $parameter->value());
    }
}
