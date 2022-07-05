<?php

namespace Illuminate\Console\View\Components\Mutators;

class EnsureRelativePaths
{
    /**
     * Ensures the given string only contains relative paths.
     *
     * @param  string  $string
     * @return string
     */
    public function __invoke($string)
    {
        $string = str_replace(base_path().'/', '', $string);

        return $string;
    }
}
