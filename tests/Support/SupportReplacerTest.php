<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Replacer;
use PHPUnit\Framework\TestCase;

class SupportReplacerTest extends TestCase
{
    public function testNumbers()
    {
        $this->assertSame('5551234567', Replacer::numbers('(555) 123-4567'));
        $this->assertSame('443', Replacer::numbers('L4r4v3l!'));
        $this->assertSame('', Replacer::numbers('Laravel!'));

        $arrayValue = ['(555) 123-4567', 'L4r4v3l', 'Laravel!'];
        $arrayExpected = ['5551234567', '443', ''];
        $this->assertSame($arrayExpected, Replacer::numbers($arrayValue));
    }

    public function testReplace()
    {
        $this->assertSame('foo bar laravel', Replacer::replace('baz', 'laravel', 'foo bar baz'));
        $this->assertSame('foo bar laravel', Replacer::replace('baz', 'laravel', 'foo bar Baz', false));
        $this->assertSame('foo bar baz 8.x', Replacer::replace('?', '8.x', 'foo bar baz ?'));
        $this->assertSame('foo bar baz 8.x', Replacer::replace('x', '8.x', 'foo bar baz X', false));
        $this->assertSame('foo/bar/baz', Replacer::replace(' ', '/', 'foo bar baz'));
        $this->assertSame('foo bar baz', Replacer::replace(['?1', '?2', '?3'], ['foo', 'bar', 'baz'], '?1 ?2 ?3'));
        $this->assertSame(['foo', 'bar', 'baz'], Replacer::replace(collect(['?1', '?2', '?3']), collect(['foo', 'bar', 'baz']), collect(['?1', '?2', '?3'])));
    }

    public function testReplaceArray()
    {
        $this->assertSame('foo/bar/baz', Replacer::replaceArray('?', ['foo', 'bar', 'baz'], '?/?/?'));
        $this->assertSame('foo/bar/baz/?', Replacer::replaceArray('?', ['foo', 'bar', 'baz'], '?/?/?/?'));
        $this->assertSame('foo/bar', Replacer::replaceArray('?', ['foo', 'bar', 'baz'], '?/?'));
        $this->assertSame('?/?/?', Replacer::replaceArray('x', ['foo', 'bar', 'baz'], '?/?/?'));
        // Ensure recursive replacements are avoided
        $this->assertSame('foo?/bar/baz', Replacer::replaceArray('?', ['foo?', 'bar', 'baz'], '?/?/?'));
        // Test for associative array support
        $this->assertSame('foo/bar', Replacer::replaceArray('?', [1 => 'foo', 2 => 'bar'], '?/?'));
        $this->assertSame('foo/bar', Replacer::replaceArray('?', ['x' => 'foo', 'y' => 'bar'], '?/?'));
        // Test does not crash on bad input
        $this->assertSame('?', Replacer::replaceArray('?', [(object) ['foo' => 'bar']], '?'));
    }

    public function testReplaceFirst()
    {
        $this->assertSame('fooqux foobar', Replacer::replaceFirst('bar', 'qux', 'foobar foobar'));
        $this->assertSame('foo/qux? foo/bar?', Replacer::replaceFirst('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('foo foobar', Replacer::replaceFirst('bar', '', 'foobar foobar'));
        $this->assertSame('foobar foobar', Replacer::replaceFirst('xxx', 'yyy', 'foobar foobar'));
        $this->assertSame('foobar foobar', Replacer::replaceFirst('', 'yyy', 'foobar foobar'));
        $this->assertSame('1', Replacer::replaceFirst(0, '1', '0'));
        // Test for multibyte string support
        $this->assertSame('Jxxxnköping Malmö', Replacer::replaceFirst('ö', 'xxx', 'Jönköping Malmö'));
        $this->assertSame('Jönköping Malmö', Replacer::replaceFirst('', 'yyy', 'Jönköping Malmö'));
    }

    public function testReplaceStart()
    {
        $this->assertSame('foobar foobar', Replacer::replaceStart('bar', 'qux', 'foobar foobar'));
        $this->assertSame('foo/bar? foo/bar?', Replacer::replaceStart('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('quxbar foobar', Replacer::replaceStart('foo', 'qux', 'foobar foobar'));
        $this->assertSame('qux? foo/bar?', Replacer::replaceStart('foo/bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('bar foobar', Replacer::replaceStart('foo', '', 'foobar foobar'));
        $this->assertSame('1', Replacer::replaceStart(0, '1', '0'));
        // Test for multibyte string support
        $this->assertSame('xxxnköping Malmö', Replacer::replaceStart('Jö', 'xxx', 'Jönköping Malmö'));
        $this->assertSame('Jönköping Malmö', Replacer::replaceStart('', 'yyy', 'Jönköping Malmö'));
    }

    public function testReplaceLast()
    {
        $this->assertSame('foobar fooqux', Replacer::replaceLast('bar', 'qux', 'foobar foobar'));
        $this->assertSame('foo/bar? foo/qux?', Replacer::replaceLast('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('foobar foo', Replacer::replaceLast('bar', '', 'foobar foobar'));
        $this->assertSame('foobar foobar', Replacer::replaceLast('xxx', 'yyy', 'foobar foobar'));
        $this->assertSame('foobar foobar', Replacer::replaceLast('', 'yyy', 'foobar foobar'));
        // Test for multibyte string support
        $this->assertSame('Malmö Jönkxxxping', Replacer::replaceLast('ö', 'xxx', 'Malmö Jönköping'));
        $this->assertSame('Malmö Jönköping', Replacer::replaceLast('', 'yyy', 'Malmö Jönköping'));
    }

    public function testReplaceEnd()
    {
        $this->assertSame('foobar fooqux', Replacer::replaceEnd('bar', 'qux', 'foobar foobar'));
        $this->assertSame('foo/bar? foo/qux?', Replacer::replaceEnd('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('foobar foo', Replacer::replaceEnd('bar', '', 'foobar foobar'));
        $this->assertSame('foobar foobar', Replacer::replaceEnd('xxx', 'yyy', 'foobar foobar'));
        $this->assertSame('foobar foobar', Replacer::replaceEnd('', 'yyy', 'foobar foobar'));
        $this->assertSame('fooxxx foobar', Replacer::replaceEnd('xxx', 'yyy', 'fooxxx foobar'));

        // // Test for multibyte string support
        $this->assertSame('Malmö Jönköping', Replacer::replaceEnd('ö', 'xxx', 'Malmö Jönköping'));
        $this->assertSame('Malmö Jönkyyy', Replacer::replaceEnd('öping', 'yyy', 'Malmö Jönköping'));
    }

    public function testSubstrReplace()
    {
        $this->assertSame('12:00', Replacer::substrReplace('1200', ':', 2, 0));
        $this->assertSame('The Laravel Framework', Replacer::substrReplace('The Framework', 'Laravel ', 4, 0));
        $this->assertSame('Laravel – The PHP Framework for Web Artisans', Replacer::substrReplace('Laravel Framework', '– The PHP Framework for Web Artisans', 8));
    }
}
