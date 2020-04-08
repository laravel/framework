<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\HtmlString;
use PHPUnit\Framework\TestCase;

class SupportHtmlStringTest extends TestCase
{
    /**
     * @param  string  $string
     * @return \Illuminate\Support\HtmlString
     */
    protected function htmlstring($string = '')
    {
        return new HtmlString($string);
    }

    public function testToHtml()
    {
        $this->assertSame('<h1>foo</h1>', $this->htmlstring('<h1>foo</h1>')->toHtml());
    }

    public function testToString()
    {
        $this->assertSame('<h1>foo</h1>', (string) $this->htmlstring('<h1>foo</h1>'));
    }
}
