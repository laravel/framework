<?php

namespace Illuminate\Console\View\Components\Concerns;

trait EnsurePunctuation
{
    /**
     * Ensures the given string ends with a punctuation.
     *
     * @param  string  $string
     * @param  string  $default
     * @return string
     */
    protected static function ensurePunctuation($string, $default = '.')
    {
        if (! str($string)->endsWith(['.', '?', '!', ':'])) {
            return "$string$default";
        }

        return $string;
    }
}
