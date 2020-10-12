<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\HtmlString;
use PHPUnit\Framework\TestCase;

class SupportHtmlStringTest extends TestCase
{
    public function testToHtml()
    {
        $str = '<h1>foo</h1>';
        $html = new HtmlString('<h1>foo</h1>');
        $this->assertEquals($str, $html->toHtml());
    }

    public function testToString()
    {
        $str = '<h1>foo</h1>';
        $html = new HtmlString('<h1>foo</h1>');
        $this->assertEquals($str, (string) $html);
    }

    public function testIsEmpty()
    {
        $this->assertTrue((new HtmlString(''))->isEmpty());
    }

    public function testIsNotEmpty()
    {
        $this->assertTrue((new HtmlString('foo'))->isNotEmpty());
    }
}
