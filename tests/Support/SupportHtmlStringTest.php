<?php

use Illuminate\Support\HtmlString;

class SupportHtmlStringTest extends PHPUnit_Framework_TestCase
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
}
