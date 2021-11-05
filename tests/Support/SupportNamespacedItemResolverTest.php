<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\NamespacedItemResolver;
use PHPUnit\Framework\TestCase;

class SupportNamespacedItemResolverTest extends TestCase
{
    public function testResolution()
    {
        $r = new NamespacedItemResolver;

        $this->assertEquals(['foo', 'bar', 'baz'], $r->parseKey('foo::bar.baz'));
        $this->assertEquals(['foo', 'bar', null], $r->parseKey('foo::bar'));
        $this->assertEquals([null, 'bar', 'baz'], $r->parseKey('bar.baz'));
        $this->assertEquals([null, 'bar', null], $r->parseKey('bar'));
    }

    public function testParsedItemsAreCached()
    {
        $r = $this->getMockBuilder(NamespacedItemResolver::class)->onlyMethods(['parseBasicSegments', 'parseNamespacedSegments'])->getMock();
        $r->setParsedKey('foo.bar', ['foo']);
        $r->expects($this->never())->method('parseBasicSegments');
        $r->expects($this->never())->method('parseNamespacedSegments');

        $this->assertEquals(['foo'], $r->parseKey('foo.bar'));
    }

    public function testParsedItemsMayBeFlushed()
    {
        $r = $this->getMockBuilder(NamespacedItemResolver::class)->onlyMethods(['parseBasicSegments', 'parseNamespacedSegments'])->getMock();
        $r->expects($this->once())->method('parseBasicSegments')->will(
            $this->returnValue(['bar'])
        );

        $r->setParsedKey('foo.bar', ['foo']);
        $r->flushParsedKeys();

        $this->assertEquals(['bar'], $r->parseKey('foo.bar'));
    }
}
