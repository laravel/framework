<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesDusk
{
    /**
     * Compile dusk hooks into empty strings. This is needed
     * because Dusk doesn't register itself in production.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileDusk($expression)
    {
        return '';
    }
}
