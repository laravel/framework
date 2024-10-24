<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesAttributes
{
    /**
     * Compile the conditional class statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileAttributes($expression)
    {
        $expression = is_null($expression) ? '([])' : $expression;
        return "<?php echo \Illuminate\Support\Arr::toHtmlAttributes{$expression}; ?>";
    }
}
