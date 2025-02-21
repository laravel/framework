<?php

namespace Illuminate\Mail;

use Illuminate\Contracts\Support\DeferringDisplayableValue;
use Illuminate\Support\HtmlString;
use League\CommonMark\MarkdownConverter;

class MarkdownString extends HtmlString implements DeferringDisplayableValue
{
    /**
     * The Markdown Converter implementation.
     *
     * @var \League\CommonMark\MarkdownConverter
     */
    protected $converter;

    /**
     * Create a new HTML string instance.
     *
     * @param  string  $html
     * @return void
     */
    public function __construct($html = '', MarkdownConverter $converter)
    {
        parent::__construct($html);

        $this->converter = $converter;
    }

    /**
     * Get the HTML string.
     *
     * @return string
     */
    #[\Override]
    public function toHtml()
    {
        return $this->converter->convert($this->html)->getContent();
    }

    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return \Illuminate\Contracts\Support\Htmlable|string
     */
    public function resolveDisplayableValue()
    {
        $replacements = [
            '[' => '\[',
        ];

        $html = str_replace(array_keys($replacements), array_values($replacements), $this->html);

        return $this->converter->convert($html)->getContent();
    }
}
