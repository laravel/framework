<?php

namespace Illuminate\Tests\Markdown;

use Illuminate\Container\Container;
use Illuminate\Contracts\Markdown\Markdown;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Application;
use Illuminate\Markdown\CommonMarkRenderer;
use Illuminate\Markdown\MarkdownServiceProvider;
use Illuminate\Markdown\ParsedownRenderer;
use Illuminate\Markdown\PhpMarkdownRenderer;
use PHPUnit\Framework\TestCase;

class MarkdownTest extends TestCase
{
    public function testCanLocateAndRender()
    {
        $app = new Application();
        $app->register(new MarkdownServiceProvider($app));

        $markdown = $app->make(Markdown::class);
        $this->assertInstanceOf(Markdown::class, $markdown);

        $html = $markdown->render('# Hello World');
        $this->assertInstanceOf(Htmlable::class, $html);
        $this->assertSame('<h1>Hello World</h1>', $html->toHtml());
    }

    public function rendererProvider()
    {
        return [
            [CommonMarkRenderer::class],
            [ParsedownRenderer::class],
            [PhpMarkdownRenderer::class],
        ];
    }

    /**
     * @dataProvider rendererProvider
     */
    public function testRender($markdown)
    {
        $renderer = $markdown::create(new Container());
        $this->assertSame('', $renderer->render('')->toHtml());
        $this->assertSame('<h2>Hello There!</h2>', $renderer->render('## Hello There!')->toHtml());
        $this->assertSame("<ul>\n<li>hello\n*world</li>\n</ul>", $renderer->render("* hello\n*world")->toHtml());
    }
}
