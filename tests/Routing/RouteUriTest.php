<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Routing\RouteUri;
use PHPUnit\Framework\TestCase;

class RouteUriTest extends TestCase
{
    public function testRouteUrisAreProperlyParsed()
    {
        $parsed = RouteUri::parse('/foo');
        $this->assertSame('/foo', $parsed->uri);
        $this->assertEquals([], $parsed->bindingFields);

        $parsed = RouteUri::parse('/foo/{bar}');
        $this->assertSame('/foo/{bar}', $parsed->uri);
        $this->assertEquals([], $parsed->bindingFields);

        $parsed = RouteUri::parse('/foo/{bar:slug}');
        $this->assertSame('/foo/{bar}', $parsed->uri);
        $this->assertEquals(['bar' => 'slug'], $parsed->bindingFields);

        $parsed = RouteUri::parse('/foo/{bar}/baz/{qux:slug}');
        $this->assertSame('/foo/{bar}/baz/{qux}', $parsed->uri);
        $this->assertEquals(['qux' => 'slug'], $parsed->bindingFields);

        $parsed = RouteUri::parse('/foo/{bar}/baz/{qux:slug?}');
        $this->assertSame('/foo/{bar}/baz/{qux?}', $parsed->uri);
        $this->assertEquals(['qux' => 'slug'], $parsed->bindingFields);

        $parsed = RouteUri::parse('/foo/{bar}/baz/{qux:slug?}/{test:id?}');
        $this->assertSame('/foo/{bar}/baz/{qux?}/{test?}', $parsed->uri);
        $this->assertEquals(['qux' => 'slug', 'test' => 'id'], $parsed->bindingFields);
    }

    public function testRouteUriTypesAreProperlyParsed()
    {
        $int = RouteUri::getExpressionForType('int');
        $alpha = RouteUri::getExpressionForType('alpha');
        $alnum = RouteUri::getExpressionForType('alnum');

        $parsed = RouteUri::parse('/foo/{int bar}');
        $this->assertSame('/foo/{bar}', $parsed->uri);
        $this->assertEquals([], $parsed->bindingFields);
        $this->assertEquals(['bar' => $int], $parsed->wheres);

        $parsed = RouteUri::parse('/foo/{int bar:slug}');
        $this->assertSame('/foo/{bar}', $parsed->uri);
        $this->assertEquals(['bar' => 'slug'], $parsed->bindingFields);
        $this->assertEquals(['bar' => $int], $parsed->wheres);

        $parsed = RouteUri::parse('/foo/{int bar}/baz/{alpha qux:slug}');
        $this->assertSame('/foo/{bar}/baz/{qux}', $parsed->uri);
        $this->assertEquals(['qux' => 'slug'], $parsed->bindingFields);
        $this->assertEquals(['bar' => $int, 'qux' => $alpha], $parsed->wheres);

        $parsed = RouteUri::parse('/foo/{int bar}/baz/{alnum qux:slug?}');
        $this->assertSame('/foo/{bar}/baz/{qux?}', $parsed->uri);
        $this->assertEquals(['qux' => 'slug'], $parsed->bindingFields);
        $this->assertEquals(['bar' => $int, 'qux' => $alnum], $parsed->wheres);

        $parsed = RouteUri::parse('/foo/{int bar}/baz/{qux:slug?}/{alnum test:id?}');
        $this->assertSame('/foo/{bar}/baz/{qux?}/{test?}', $parsed->uri);
        $this->assertEquals(['qux' => 'slug', 'test' => 'id'], $parsed->bindingFields);
        $this->assertEquals(['bar' => $int, 'test' => $alnum], $parsed->wheres);
    }
}
