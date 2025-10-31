<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesWhen
{
    /**
     * Compile the "@when" directive into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileWhen($expression)
    {
        return "<?php echo when{$expression}; ?>";
    }
}
