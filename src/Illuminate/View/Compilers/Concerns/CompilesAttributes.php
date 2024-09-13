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
        return "<?php echo \Illuminate\Support\Collection::make{$expression}->map(function (\$value, \$key) { if (\$key && \$value) { return \$value ? \$key : null; } else { return \$value; } })->filter()->implode(' '); ?>";
    }
}
