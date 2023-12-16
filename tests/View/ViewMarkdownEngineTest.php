<?php

namespace Illuminate\Tests\View;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\ComponentSlot;
use Illuminate\View\Engines\MarkdownEngine;
use Illuminate\View\Factory;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class ViewMarkdownEngineTest extends TestCase
{
    public function testViewsMayBeProperlyRendered()
    {
        $engine = new MarkdownEngine(
            new Filesystem,
            new GithubFlavoredMarkdownConverter,
            m::mock(Factory::class)
        );

        $expected = <<<MARKDOWN
        <h1>Markdown Example</h1>
        <p>This is an example <a href="https://daringfireball.net/projects/markdown/">markdown</a> file.</p>
        <ul>
        <li>Markdown</li>
        <li>is</li>
        <li>wonderful.</li>
        </ul>
        MARKDOWN;

        $this->assertSame(trim($expected), trim($engine->get(__DIR__.'/fixtures/markdown.md')));
    }

    public function testViewsCanBeRenderedInLayout()
    {
        $expected = <<<MARKDOWN
        <h1>Markdown Example</h1>
        <p>This is an example <a href="https://daringfireball.net/projects/markdown/">markdown</a> file.</p>
        <ul>
        <li>Markdown</li>
        <li>is</li>
        <li>wonderful.</li>
        </ul>
        MARKDOWN;

        $view = m::mock(Factory::class);

        $view->shouldReceive('make')
            ->once()
            ->withArgs(function ($view, $data) use ($expected) {
                return $view === 'layouts.app'
                    && is_array($data)
                    && $data['slot'] instanceof ComponentSlot
                    && trim((string) $data['slot']) === trim($expected);
            })
            ->andReturn('rendered with blade');

        $engine = new MarkdownEngine(new Filesystem, new GithubFlavoredMarkdownConverter, $view);
        $engine->setLayout('layouts.app');

        $this->assertSame('rendered with blade', $engine->get(__DIR__.'/fixtures/markdown.md'));
    }
}
