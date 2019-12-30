<?php

namespace Illuminate\Contracts\Markdown;

interface Markdown
{
    /**
     * Render the given Markdown string as HTML.
     *
     * @param  string  $markdown
     * @return \Illuminate\Contracts\Support\Htmlable
     */
    public function render($markdown);
}
