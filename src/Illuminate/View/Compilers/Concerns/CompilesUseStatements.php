<?php

declare(strict_types=1);

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
        $expression = preg_replace("/[\(\)]/", '', $expression);

        if (! str_starts_with($expression, '[')) {
            $segments = explode(',', $expression);

            $namespace = trim($segments[0], " '\"");
            $alias = isset($segments[1]) ? ' as '.trim($segments[1], " '\"") : '';

            return "<?php use \\{$namespace}{$alias}; ?>";
        }

        $namespaces = eval("return $expression;");

        $useStatements = '<?php ';

        $useStatements .= implode(' ', array_map(function ($namespace, $alias) {
            if (is_numeric($namespace)) {
                return 'use \\'.trim($alias, " '\"").';';
            } else {
                return 'use \\'.trim($namespace, " '\"").' as '.trim($alias, " '\"").';';
            }
        }, array_keys($namespaces), $namespaces));

        return $useStatements.' ?>';
    }
}
