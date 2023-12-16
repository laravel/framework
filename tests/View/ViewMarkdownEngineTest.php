<?php

namespace Illuminate\Tests\View;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\MarkdownEngine;
use Illuminate\View\Engines\PhpEngine;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use PHPUnit\Framework\TestCase;

class ViewMarkdownEngineTest extends TestCase
{
    public function testViewsMayBeProperlyRendered()
    {
        $engine = new MarkdownEngine(new Filesystem, new GithubFlavoredMarkdownConverter);

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
}
