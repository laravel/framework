<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesClasses
{
    /**
     * Compile the conditional class statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileClass($expression)
    {
        $expression = is_null($expression) ? '([])' : $expression;

        return "<?php \$__classes = \Illuminate\Support\Arr::toCssClasses{$expression}; echo \$__classes !== '' ? 'class=\"'.\$__classes.'\"' : ''; ?>";
    }
}
