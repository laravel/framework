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
        $segments = explode(',', preg_replace("/[\(\)]/", '', $expression));

        $variable = trim($segments[0], " '\"");

        $service = trim($segments[1]);

        $enum = trim($service, " '\"");

        if (enum_exists($enum)) {
            return "<?php class_alias(\\{$enum}::class, '{$variable}'); ?>";
        }

        return "<?php \${$variable} = app({$service}); ?>";
    }
}
