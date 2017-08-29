<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesDefaults
{
    /*
     * Compile the default statements into valid PHP.
     *
     * @param  string $expression
     * @return string
     */
    protected function compileDefault($expression)
    {
        $segments = explode(',', trim($expression, '()'));

        $variable = preg_replace("/[\(\)\\\"\']/", '', $segments[0]);

        $value = trim($segments[1]);

        return "<?php \${$variable} = \${$variable} ?? {$value}; ?>";
    }

    /*
     * Compile the defaults statements into valid PHP.
     *
     * @param  string $defaults
     * @return string
     */
    protected function compileDefaults($defaults)
    {
        return '<?php foreach ('.$defaults.' as $variable => $value) {'.PHP_EOL.
            '$$variable = $$variable ?? $value;'.PHP_EOL.
        '} ?>';
    }
}
