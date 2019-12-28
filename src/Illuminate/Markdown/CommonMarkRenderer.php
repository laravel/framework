<?php

namespace Illuminate\Markdown;

use Illuminate\Contracts\Markdown\Markdown;
use Illuminate\Support\HtmlString;
use League\CommonMark\ConverterInterface;

class CommonMarkRenderer implements Markdown
{
    /**
     * The CommonMark converter.
     *
     * @var \League\CommonMark\ConverterInterface
     */
    protected $commonmark;

    /**
     * Create a new CommonMark renderer instance.
     *
     * @param  \League\CommonMark\ConverterInterface  $commonmark
     * @return void
     */
    public function __construct(ConverterInterface $commonmark)
    {
        $this->commonmark = $commonmark;
    }

    /**
     * Render the given Markdown string as HTML.
     *
     * @param  string  $markdown
     * @return \Illuminate\Contracts\Support\Htmlable
     */
    public function render($markdown)
    {
        return new HtmlString(
            rtrim($this->commonmark->convertToHtml($markdown))
        );
    }
}
