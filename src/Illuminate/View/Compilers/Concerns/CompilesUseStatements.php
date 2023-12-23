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

        $namespaces = str_replace("'", '"', $expression);
        $namespaces = preg_replace('/\\\\/', '\\', $namespaces);
        $namespaces = trim($namespaces, '[]');
        $namespaces = explode(',', $namespaces);

        $useStatements = '<?php';

        foreach ($namespaces as $namespace) {
            [$use, $as] = array_pad(explode('=>', $namespace, 2), 2, null);
            $use = trim($use);
            $as = $as !== null ? trim($as) : null;
            $use = str_replace(['"', "'"], '', $use);
            $as = $as !== null ? str_replace(['"', "'"], '', $as) : null;

            if ($as === null) {
                $useStatements .= ' use \\'.trim($use, " '\"").'; ';
            } else {
                $useStatements .= ' use \\'.trim($use, " '\"").' as '.trim($as, " '\"").'; ';
            }
        }

        return $useStatements.'?>';
    }
}
