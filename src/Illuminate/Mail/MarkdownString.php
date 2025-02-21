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
     * Convert markdown to instance of HtmlString.
     *
     * @return \Illuminate\Contracts\Support\Htmlable
     */
    public function convertMarkdownToHtml()
    {
        return new HtmlString(
            $this->converter()->convert($this->html)->getContent()
        );
    }

    /**
     * Convert encoded markdown to instance of HtmlString.
     *
     * @return \Illuminate\Contracts\Support\Htmlable
     */
    public function convertEncodedMarkdownToHtml()
    {
        $replacements = [
            '[' => '\[',
            '<' => '\<',
        ];

        $html = str_replace(array_keys($replacements), array_values($replacements), $this->html);

        return new HtmlString($this->converter([
            'html_input' => 'escape',
        ])->convert($html)->getContent());
    }

    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return \Illuminate\Contracts\Support\Htmlable
     */
    public function resolveDisplayableValue()
    {
        return $this->convertEncodedMarkdownToHtml();
    }

    /**
     * Get the HTML string.
     *
     * @return string
     */
    #[\Override]
    public function toHtml()
    {
        return $this->convertMarkdownToHtml()->toHtml();
    }

    /**
     * Resolve the Markdown Converter.
     *
     * @param  array  $config
     * @return \League\CommonMark\MarkdownConverter
     */
    protected function converter(array $config = [])
    {
        $environment = new Environment(array_merge([
            'allow_unsafe_links' => false,
        ], $config));

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);

        return new MarkdownConverter($environment);
    }
}
