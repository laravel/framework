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
        $expression = preg_replace('/[()]/', '', $expression);

        // Preserve grouped imports as-is...
        if (str_contains($expression, '{')) {
            $use = ltrim(trim($expression, " '\""), '\\');

            return "<?php use \\{$use}; ?>";
        }

        $segments = explode(',', $expression);

        $use = ltrim(trim($segments[0], " '\""), '\\');
        $as = isset($segments[1]) ? ' as '.trim($segments[1], " '\"") : '';

        return "<?php use \\{$use}{$as}; ?>";
    }
}
