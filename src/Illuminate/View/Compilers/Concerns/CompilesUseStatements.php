<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesUseStatements
{
    /**
     * Compile the use statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileUse($expression)
    {
        $segments = explode(',', preg_replace("/[\(\)]/", '', $expression));

        $use = trim($segments[0], " '\"");
        $as = isset($segments[1]) ? ' as '.ltrim(trim($segments[0], " '\""), '\\') : '';

        return "<?php use \\{$use}{$as}; ?>";
    }
}
