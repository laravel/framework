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

        // if it is not start with '[' therefor it is single namespace
        // and we can use it as it is
        if (! str_starts_with($expression, '[')) {
            $segments = explode(',', $expression);

            $namespace = ltrim(trim($segments[0], " '\""), '\\');
            $alias = isset($segments[1]) ? ' as '.trim($segments[1], " '\"") : '';

            return "<?php use \\{$namespace}{$alias}; ?>";
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
