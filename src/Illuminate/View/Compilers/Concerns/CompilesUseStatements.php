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
        if (preg_match('/\(\s*\[\s*(.*)\s*\]\s*\)/s', $expression)) {
            return $this->compileMultipleUseStatements(preg_replace('/\(\s*\[\s*(.*)\s*\]\s*\)/s', '$1', $expression));
        }

        $segments = explode(',', preg_replace("/[\(\)]/", '', $expression));

        $use = trim($segments[0], " '\"\n\r\t\v\0");
        $as = isset($segments[1]) ? ' as '.trim($segments[1], " '\"\n\r\t\v\0") : '';

        return "<?php use \\{$use}{$as}; ?>";
    }

    /**
     * Compile multiple use statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileMultipleUseStatements($expression)
    {
        return collect(explode(',', $expression))
            ->map(function ($use) {
                $use = str_replace('=>', ',', $use);

                return $this->compileUse($use);
            })
            ->implode("\n");
    }
}
