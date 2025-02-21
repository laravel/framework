<?php

namespace Illuminate\Mail;

use Illuminate\Contracts\Support\DeferringDisplayableValue;
use Illuminate\Support\HtmlString;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
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
    public function __construct($html = '')
    {
        parent::__construct($html);

        $environment = new Environment([
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);

        $this->converter = new MarkdownConverter($environment);
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
