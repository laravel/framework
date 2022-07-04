<?php

namespace Illuminate\Console\View\Components\Concerns;

trait EnsureRelativePaths
{
    /**
     * Ensures the given string only contains relative paths.
     *
     * @param  string  $string
     * @return string
     */
    protected static function ensureRelativePaths($string)
    {
        $string = str_replace(base_path().'/', '', $string);

        return $string;
    }
}
