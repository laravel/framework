<?php

namespace Illuminate\Console\View\Components\Mutators;

class EnsureNoPunctuation
{
    /**
     * Ensures the given string does not end with a dot.
     *
     * @param  string  $string
     * @return string
     */
    public function __invoke($string)
    {
        if (str($string)->endsWith(['.', '?', '!', ':'])) {
            return substr_replace($string, '', -1);
        }

        return $string;
    }
}
