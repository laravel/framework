<?php

use Illuminate\View\Expression;

class SupportHtmlStringTest extends PHPUnit_Framework_TestCase
{
    public function testToHtml()
    {
        $str = '<h1>foo</h1>';
        $html = new Expression('<h1>foo</h1>');
        $this->assertEquals($str, $html->toHtml());
    }

    public function testToString()
    {
        $str = '<h1>foo</h1>';
        $html = new Expression('<h1>foo</h1>');
        $this->assertEquals($str, (string) $html);
    }
}
