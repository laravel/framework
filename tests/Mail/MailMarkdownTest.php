<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Markdown;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class MailMarkdownTest extends TestCase
{
    public function testRenderFunctionReturnsHtml(): void
    {
        $viewFactory = m::mock(Factory::class);
        $engineResolver = m::mock(EngineResolver::class);
        $bladeCompiler = m::mock(BladeCompiler::class);
        $viewFactory->shouldReceive('getEngineResolver')->andReturn($engineResolver);
        $engineResolver->shouldReceive('resolve->getCompiler')->andReturn($bladeCompiler);
        $bladeCompiler->shouldReceive('usingEchoFormat')
            ->with('new \Illuminate\Support\EncodedHtmlString(%s)', m::type('Closure'))
            ->andReturnUsing(fn ($echoFormat, $callback) => $callback());

        $markdown = new Markdown($viewFactory);
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $markdown->htmlComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('exists')->with('mail.default')->andReturn(false);
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('mail::themes.default', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('<html></html>', 'body {}');

        $result = $markdown->render('view', []);

        $this->assertStringContainsString('<html></html>', $result);
    }

    public function testRenderFunctionReturnsHtmlWithCustomTheme(): void
    {
        $viewFactory = m::mock(Factory::class);
        $engineResolver = m::mock(EngineResolver::class);
        $bladeCompiler = m::mock(BladeCompiler::class);
        $viewFactory->shouldReceive('getEngineResolver')->andReturn($engineResolver);
        $engineResolver->shouldReceive('resolve->getCompiler')->andReturn($bladeCompiler);
        $bladeCompiler->shouldReceive('usingEchoFormat')
            ->with('new \Illuminate\Support\EncodedHtmlString(%s)', m::type('Closure'))
            ->andReturnUsing(fn ($echoFormat, $callback) => $callback());

        $markdown = new Markdown($viewFactory);
        $markdown->theme('yaz');
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $markdown->htmlComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('exists')->with('mail.yaz')->andReturn(true);
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('mail.yaz', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('<html></html>', 'body {}');

        $result = $markdown->render('view', []);

        $this->assertStringContainsString('<html></html>', $result);
    }

    public function testRenderFunctionReturnsHtmlWithCustomThemeWithMailPrefix(): void
    {
        $viewFactory = m::mock(Factory::class);
        $engineResolver = m::mock(EngineResolver::class);
        $bladeCompiler = m::mock(BladeCompiler::class);
        $viewFactory->shouldReceive('getEngineResolver')->andReturn($engineResolver);
        $engineResolver->shouldReceive('resolve->getCompiler')->andReturn($bladeCompiler);
        $bladeCompiler->shouldReceive('usingEchoFormat')
            ->with('new \Illuminate\Support\EncodedHtmlString(%s)', m::type('Closure'))
            ->andReturnUsing(fn ($echoFormat, $callback) => $callback());

        $markdown = new Markdown($viewFactory);
        $markdown->theme('mail.yaz');
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $markdown->htmlComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('exists')->with('mail.yaz')->andReturn(true);
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('mail.yaz', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('<html></html>', 'body {}');

        $result = $markdown->render('view', []);

        $this->assertStringContainsString('<html></html>', $result);
    }

    public function testRenderTextReturnsText(): void
    {
        $viewFactory = m::mock(Factory::class);
        $markdown = new Markdown($viewFactory);
        $viewFactory->shouldReceive('flushFinderCache')->once();
        $viewFactory->shouldReceive('replaceNamespace')->once()->with('mail', $markdown->textComponentPaths())->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->andReturn('text');

        $result = $markdown->renderText('view', [])->toHtml();

        $this->assertSame('text', $result);
    }

    public function testParseReturnsParsedMarkdown(): void
    {
        $viewFactory = m::mock(Factory::class);
        $markdown = new Markdown($viewFactory);

        $result = $markdown->parse('# Something')->toHtml();

        $this->assertSame("<h1>Something</h1>\n", $result);
    }

    public function testParseWithCustomExtensionsViaConfig(): void
    {
        $this->app['config']->set('mail.markdown.extensions', [
            \League\CommonMark\Extension\Strikethrough\StrikethroughExtension::class,
        ]);

        $result = Markdown::parse('~~strikethrough text~~')->toHtml();

        $this->assertStringContainsString('<del>', $result);
        $this->assertStringContainsString('strikethrough text', $result);
        $this->assertStringContainsString('</del>', $result);
    }

    public function testParseWithoutCustomExtensionsDoesNotApplyThem(): void
    {
        $this->app['config']->set('mail.markdown.extensions', []);

        $result = Markdown::parse('~~strikethrough text~~')->toHtml();

        $this->assertStringNotContainsString('<del>', $result);
        $this->assertStringContainsString('~~strikethrough text~~', $result);
    }

    public function testParseWithMultipleCustomExtensions(): void
    {
        $this->app['config']->set('mail.markdown.extensions', [
            \League\CommonMark\Extension\Strikethrough\StrikethroughExtension::class,
            \League\CommonMark\Extension\TaskList\TaskListExtension::class,
        ]);

        $strikethroughResult = Markdown::parse('~~strikethrough~~')->toHtml();
        $this->assertStringContainsString('<del>', $strikethroughResult);

        $taskListResult = Markdown::parse('- [ ] Task item')->toHtml();
        $this->assertStringContainsString('type="checkbox"', $taskListResult);
    }
}
