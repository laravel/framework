<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesLogger
{
    /**
     * Compile the logger statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    public function compileLogger(string $expression)
    {
        return "<?php logger{$expression}; ?>";
    }
}
