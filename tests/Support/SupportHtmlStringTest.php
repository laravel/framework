<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\HtmlString;
use PHPUnit\Framework\TestCase;

class SupportHtmlStringTest extends TestCase
{
    public function testToHtml(): void
    {
        // Check if HtmlString correctly converts a basic HTML string
        $str = '<h1>foo</h1>';
        $html = new HtmlString('<h1>foo</h1>');
        $this->assertEquals($str, $html->toHtml());

        // Check if HtmlString correctly preserves leading blank spaces in the HTML string
        $startWithBlankSpaces = '   <h1>      foo</h1>';
        $html = new HtmlString('   <h1>      foo</h1>');
        $this->assertEquals($startWithBlankSpaces, $html->toHtml());

        // Check if HtmlString correctly preserves trailing blank spaces in the HTML string
        $endsWithBlankSpaces = '<h1>foo       </h1>   ';
        $html = new HtmlString('<h1>foo       </h1>   ');
        $this->assertEquals($endsWithBlankSpaces, $html->toHtml());

        // Check if HtmlString correctly handles an empty string
        $emptyHtml = new HtmlString('');
        $this->assertEquals('', $emptyHtml->toHtml());

        // Check if HtmlString correctly converts a plain text string
        $str = 'foo bar';
        $html = new HtmlString($str);
        $this->assertEquals($str, $html->toHtml());
    }

    public function testToString()
    {
        $str = '<h1>foo</h1>';
        $html = new HtmlString('<h1>foo</h1>');
        $this->assertEquals($str, (string) $html);

        // Check if HtmlString gracefully handles a null value
        $html = new HtmlString(null);
        $this->assertIsString((string) $html);
    }

    public function testIsEmpty(): void
    {
        // Check if HtmlString correctly identifies an empty string as empty
        $this->assertTrue((new HtmlString(''))->isEmpty());

        // Check if HtmlString identifies a null value as empty
        $this->assertTrue((new HtmlString(null))->isEmpty());

        // HtmlString with whitespace should not be considered as empty
        $this->assertFalse((new HtmlString('   '))->isEmpty());

        // HtmlString with content should not be considered as empty
        $this->assertFalse((new HtmlString('<p>Hello</p>'))->isEmpty());
    }

    public function testIsNotEmpty()
    {
        $this->assertTrue((new HtmlString('foo'))->isNotEmpty());
    }
}
