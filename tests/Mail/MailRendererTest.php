<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Renderer;
use Illuminate\View\Factory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class MailRendererTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testRenderFunctionReturnsHtml()
    {
        $viewFactory = m::mock(Factory::class);
        $renderer = new Renderer($viewFactory);
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $renderer->htmlComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('mail::themes.default', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('<html></html>', 'body {}');

        $result = $renderer->render('view', []);

        $this->assertTrue(strpos($result, '<html></html>') !== false);
    }

    public function testRenderFunctionReturnsHtmlWithCustomTheme()
    {
        $viewFactory = m::mock(Factory::class);
        $renderer = new Renderer($viewFactory);
        $renderer->theme('yaz');
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $renderer->htmlComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('mail::themes.yaz', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('<html></html>', 'body {}');

        $result = $renderer->render('view', []);

        $this->assertTrue(strpos($result, '<html></html>') !== false);
    }

    public function testRenderTextReturnsText()
    {
        $viewFactory = m::mock(Factory::class);
        $renderer = new Renderer($viewFactory);
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $renderer->textComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->andReturn('text');

        $result = $renderer->renderText('view', [])->toHtml();

        $this->assertSame('text', $result);
    }
}
