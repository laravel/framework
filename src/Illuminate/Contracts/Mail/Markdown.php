<?php

namespace Illuminate\Contracts\Mail;

interface Markdown
{
    /**
     * Render the Markdown template into HTML.
     *
     * @param  string  $view
     * @param  array  $data
     * @param  \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles|null  $inliner
     * @return \Illuminate\Support\HtmlString
     */
    public function render($view, array $data = [], $inliner = null);

    /**
     * Render the Markdown template into HTML.
     *
     * @param  string  $view
     * @param  array  $data
     * @return \Illuminate\Support\HtmlString
     */
    public function renderText($view, array $data = []);

    /**
     * Parse the given Markdown text into HTML.
     *
     * @param  string  $text
     * @return string
     */
    public function parse($text);
}
