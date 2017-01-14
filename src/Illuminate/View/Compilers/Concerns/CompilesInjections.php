<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesInjections
{
    /**
     * Compile the inject statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileInject($expression)
    {
        $segments = explode(',', preg_replace("/[\(\)\\\"\']/", '', $expression));

        return '<?php $'.trim($segments[0])." = app('".trim($segments[1])."'); ?>";
    }
}
