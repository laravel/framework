<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Support\Str;

trait CompilesString
{
    /**
     * String to lower.
     *
     * @param $string
     * @return string
     */
    protected function compileLower($string)
    {
        return Str::lower($string);
    }

    /**
     * String to upper.
     *
     * @param $string
     * @return string
     */
    protected function compileUpper($string)
    {
        return Str::upper($string);
    }
}
