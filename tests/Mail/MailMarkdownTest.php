<?php

namespace Illuminate\Tests\Mail;

use Illuminate\Mail\Markdown;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

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
        $viewFactory->shouldReceive('make')->with('mail::themes.default', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('body {}', '<html></html>');

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
        $viewFactory->shouldReceive('make')->with('mail.yaz', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('body {}', '<html></html>');

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
        $viewFactory->shouldReceive('make')->with('mail.yaz', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();
        $viewFactory->shouldReceive('render')->twice()->andReturn('body {}', '<html></html>');

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

    public function testMediaQueriesAreExtractedFromThemeCss(): void
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
        $viewFactory->shouldReceive('make')->with('mail::themes.default', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();

        $cssWithMedia = 'body { color: #000; } @media (prefers-color-scheme: dark) { body { color: #fff; } } .footer { color: gray; }';
        $viewFactory->shouldReceive('render')->twice()->andReturn($cssWithMedia, '<html></html>');

        $markdown->render('view', []);

        $headStyles = Markdown::getHeadStyles();

        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $headStyles);
        $this->assertStringContainsString('body { color: #fff; }', $headStyles);
    }

    public function testMediaQueriesAreNotInlinedIntoHtml(): void
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
        $viewFactory->shouldReceive('make')->with('mail::themes.default', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();

        $cssWithMedia = 'body { color: #000; } @media (prefers-color-scheme: dark) { body { color: #fff; } }';
        $viewFactory->shouldReceive('render')->twice()->andReturn($cssWithMedia, '<html><body></body></html>');

        $result = $markdown->render('view', []);

        $this->assertStringContainsString('color: #000', $result);
        $this->assertStringNotContainsString('@media', $result);
    }

    public function testFlushStateClearsHeadStyles(): void
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
        $viewFactory->shouldReceive('make')->with('mail::themes.default', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();

        $cssWithMedia = '@media (prefers-color-scheme: dark) { body { color: #fff; } }';
        $viewFactory->shouldReceive('render')->twice()->andReturn($cssWithMedia, '<html></html>');

        $markdown->render('view', []);

        $this->assertNotEmpty(Markdown::getHeadStyles());

        Markdown::flushState();

        $this->assertEmpty(Markdown::getHeadStyles());
    }

    public function testNestedMediaQueriesAreExtractedCorrectly(): void
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
        $viewFactory->shouldReceive('make')->with('mail::themes.default', [])->andReturnSelf();
        $viewFactory->shouldReceive('make')->with('view', [])->andReturnSelf();

        $cssWithMedia = '.base { color: red; } @media (max-width: 600px) { .inner { width: 100%; } } @media (prefers-color-scheme: dark) { body { background: #000; } .panel { background: #333; } } .end { margin: 0; }';
        $viewFactory->shouldReceive('render')->twice()->andReturn($cssWithMedia, '<html></html>');

        $markdown->render('view', []);

        $headStyles = Markdown::getHeadStyles();

        $this->assertStringContainsString('@media (max-width: 600px)', $headStyles);
        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $headStyles);
        $this->assertStringContainsString('.inner { width: 100%; }', $headStyles);
        $this->assertStringContainsString('body { background: #000; }', $headStyles);
    }
}
