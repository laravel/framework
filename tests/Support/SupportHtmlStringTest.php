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
    protected function htmlString($string = '')
    {
        return new HtmlString($string);
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->htmlString('')->isEmpty());
        $this->assertFalse($this->htmlString('A')->isEmpty());
        $this->assertFalse($this->htmlString('0')->isEmpty());
    }

    public function testToHtml()
    {
        $this->assertSame('<h1>foo</h1>', $this->htmlString('<h1>foo</h1>')->toHtml());
    }

    public function testToString()
    {
        $this->assertSame('<h1>foo</h1>', (string) $this->htmlString('<h1>foo</h1>'));
    }
}
