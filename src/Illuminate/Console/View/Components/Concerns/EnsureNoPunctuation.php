<?php

namespace Illuminate\Console\View\Components\Concerns;

trait EnsureNoPunctuation
{
    /**
     * Ensures the given string does not end with a dot.
     *
     * @param  string  $string
     * @return string
     */
    protected static function ensureNoPunctuation($string)
    {
        if (str($string)->endsWith(['.', '?', '!', ':'])) {
            return substr_replace($string ,"", -1);
        }

        return $string;
    }
}
