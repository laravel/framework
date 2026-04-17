<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesStyles
{
    /**
     * Compile the conditional style statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileStyle($expression)
    {
        $expression = is_null($expression) ? '([])' : $expression;

        return "<?php \$__styles = \Illuminate\Support\Arr::toCssStyles{$expression}; echo \$__styles !== '' ? 'style=\"'.\$__styles.'\"' : ''; ?>";
    }
}
