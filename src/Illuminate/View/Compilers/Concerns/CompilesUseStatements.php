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
        $expression = preg_replace("/[\(\)]/", '', $expression);

        // if it is not start with '[' therefore it is single namespace
        // so we need to convert it to $namespace => $alias expression
        if (! str_starts_with($expression, '[')) {
            $expression = str_replace(',', '=>', $expression);
        }

        // it is start with '[' therefore it is array and it may have multiple namespaces
        // as it won't be valid json we need to parse it manually and get namespaces and aliases
        // below code is to get namespaces and aliases from [$namespace => $alias, ...] expression
        $namespaces = explode(',', trim(preg_replace('/\\\\/', '\\', $expression), '[]'));

        $useStatements = '<?php';
        foreach ($namespaces as $namespace) {
            [$use, $as] = array_pad(explode('=>', $namespace, 2), 2, '');
            $useStatements .= ' use \\'.ltrim(trim($use, " '\""), '\\').($as ? ' as '.ltrim(trim($as, " '\""), '\\') : '').';';
        }

        return $useStatements.' ?>';
    }
}
