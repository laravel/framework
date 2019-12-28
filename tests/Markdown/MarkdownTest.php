<?php

namespace Illuminate\Tests\Markdown;

use Illuminate\Contracts\Markdown\Markdown;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Application;
use Illuminate\Markdown\CommonMarkRenderer;
use Illuminate\Markdown\MarkdownLocator;
use Illuminate\Markdown\ParsedownRenderer;
use Illuminate\Markdown\PhpMarkdownRenderer;
use League\CommonMark\CommonMarkConverter;
use Michelf\Markdown as PhpMarkdown;
use Parsedown;
use PHPUnit\Framework\TestCase;

class MarkdownTest extends TestCase
{
    public function testCanLocateAndRender()
    {
        $markdown = MarkdownLocator::create(new Application);
        $this->assertInstanceOf(Markdown::class, $markdown);

        $html = $markdown->render('# Hello World');
        $this->assertInstanceOf(Htmlable::class, $html);
        $this->assertSame('<h1>Hello World</h1>', $html->toHtml());
    }

    public function rendererProvider()
    {
        return [
            [new CommonMarkRenderer(new CommonMarkConverter)],
            [new ParsedownRenderer(new Parsedown)],
            [new PhpMarkdownRenderer(new PhpMarkdown)],
        ];
    }

    /**
     * @dataProvider rendererProvider
     */
    public function testRender($markdown)
    {
        $this->assertSame('', $markdown->render('')->toHtml());
        $this->assertSame('<h2>Hello There!</h2>', $markdown->render('## Hello There!')->toHtml());
        $this->assertSame("<ul>\n<li>hello\n*world</li>\n</ul>", $markdown->render("* hello\n*world")->toHtml());
    }
}
