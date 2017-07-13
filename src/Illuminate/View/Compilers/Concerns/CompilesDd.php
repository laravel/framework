<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesDd
{
    /**
     * Compile the dd statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileDd($expression)
    {
        return "<?php dd{$expression}; ?>";
    }
}
