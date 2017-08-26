<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesDusk
{
    /**
     * Compile dusk hooks into html "dusk" attributes.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileDusk($expression)
    {
        $segments = array_map('trim', explode(',', preg_replace("/[\(\)\\\"\']/", '', $expression)));

        $selector = $segments[0];

        $stringifiedEnvironments = array_map(function ($environment) {
            return "'{$environment}'";
        }, array_merge(['testing', 'local'], array_slice($segments, 1)));

        return "<?php echo app()->environment(".implode(', ', $stringifiedEnvironments).") ? 'dusk=\"{$selector}\"' : ''; ?>";
    }
}
