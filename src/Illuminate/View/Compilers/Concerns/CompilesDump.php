<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesDump
{
    /**
     * Compile the dump statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileDump($expression)
    {
        return "<?php dd{$expression}; ?>";
    }
}
