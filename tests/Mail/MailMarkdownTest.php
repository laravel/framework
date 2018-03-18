<?php

namespace Illuminate\Tests\Mail;

use PHPUnit\Framework\TestCase;

class MailMarkdownTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testRenderFunctionReturnsHtml(): void
    {
        $viewFactory = \Mockery::mock('Illuminate\View\Factory');
        $markdown = new \Illuminate\Mail\Markdown($viewFactory);
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $markdown->htmlComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('mail::themes.default')->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('<html></html>', 'body {}');

        $result = $markdown->render('view', []);

        $this->assertTrue(strpos($result, '<html></html>') !== false);
    }

    public function testRenderFunctionReturnsHtmlWithCustomTheme(): void
    {
        $viewFactory = \Mockery::mock('Illuminate\View\Factory');
        $markdown = new \Illuminate\Mail\Markdown($viewFactory);
        $markdown->theme('yaz');
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $markdown->htmlComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('mail::themes.yaz')->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('<html></html>', 'body {}');

        $result = $markdown->render('view', []);

        $this->assertTrue(strpos($result, '<html></html>') !== false);
    }

    public function testRenderTextReturnsText(): void
    {
        $viewFactory = \Mockery::mock('Illuminate\View\Factory');
        $markdown = new \Illuminate\Mail\Markdown($viewFactory);
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $markdown->markdownComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->andReturn('text');

        $result = $markdown->renderText('view', []);

        $this->assertEquals('text', $result);
    }

    public function testParseReturnsParsedMarkdown(): void
    {
        $viewFactory = \Mockery::mock('Illuminate\View\Factory');
        $markdown = new \Illuminate\Mail\Markdown($viewFactory);

        $result = $markdown->parse('# Something');

        $this->assertEquals('<h1>Something</h1>', $result);
    }
}
