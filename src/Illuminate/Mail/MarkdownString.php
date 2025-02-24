<?php

namespace Illuminate\Mail;

use Illuminate\Support\EncodedHtmlString;
use Illuminate\Support\HtmlString;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownString extends HtmlString
{
    /**
     * Get the HTML string.
     *
     * @return string
     */
    #[\Override]
    public function toHtml()
    {
        EncodedHtmlString::encodeUsing(function ($value) {
            $replacements = [
                '[' => '\[',
                '<' => '\<',
            ];

            $html = str_replace(array_keys($replacements), array_values($replacements), $value);

            return $this->converter([
                'html_input' => 'escape',
            ])->convert($html)->getContent();
        });

        try {
            $html = $this->converter()->convert($this->html)->getContent();
        } finally {
            EncodedHtmlString::flushState();
        }

        return new HtmlString($html ?? '');
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
