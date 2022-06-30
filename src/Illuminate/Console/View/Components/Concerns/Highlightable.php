<?php

namespace Illuminate\Console\View\Components\Concerns;

trait Highlightable
{
    /**
     * Highligths dynamic content within the given string.
     *
     * @param  string  $string
     * @return string
     */
    protected static function highlightDynamicContent($string)
    {
        return preg_replace('/\[([^\]]+)\]/', '<options=bold>[$1]</>', $string);
    }
}
