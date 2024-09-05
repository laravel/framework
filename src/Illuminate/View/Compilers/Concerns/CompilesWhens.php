<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesWhens
{
    /**
     * Compile the 'when' statement into valid PHP.
     *
     * @param  string  $expression
     * @param  string  $output
     * @param  boolean  $escape
     * @return string
     */
    protected function compileWhen($expression)
    {
        $expression = $expression == '()' ? "(false,'',false)" : $expression;
        return "<?php when{$expression}; ?>";
    }
}
