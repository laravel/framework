<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\NamespacedItemResolver;
use PHPUnit\Framework\TestCase;

class SupportNamespacedItemResolverTest extends TestCase
{
    public function testResolution()
    {
        $r = new NamespacedItemResolver;

        $this->assertSame(['foo', 'bar', 'baz'], $r->parseKey('foo::bar.baz'));
        $this->assertSame(['foo', 'bar', null], $r->parseKey('foo::bar'));
        $this->assertSame([null, 'bar', 'baz'], $r->parseKey('bar.baz'));
        $this->assertSame([null, 'bar', null], $r->parseKey('bar'));
    }

    public function testParsedItemsAreCached()
    {
        $r = $this->getMockBuilder(NamespacedItemResolver::class)->onlyMethods(['parseBasicSegments', 'parseNamespacedSegments'])->getMock();
        $r->setParsedKey('foo.bar', ['foo']);
        $r->expects($this->never())->method('parseBasicSegments');
        $r->expects($this->never())->method('parseNamespacedSegments');

        $this->assertSame(['foo'], $r->parseKey('foo.bar'));
    }

    public function testParsedItemsMayBeFlushed()
    {
        $r = $this->getMockBuilder(NamespacedItemResolver::class)->onlyMethods(['parseBasicSegments', 'parseNamespacedSegments'])->getMock();
        $r->expects($this->once())->method('parseBasicSegments')->willReturn(['bar']);

        $r->setParsedKey('foo.bar', ['foo']);
        $r->flushParsedKeys();

        $this->assertSame(['bar'], $r->parseKey('foo.bar'));
    }
}
