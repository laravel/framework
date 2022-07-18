<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesString
{
    /**
     * String to lower.
     *
     * @param $expression
     * @return string
     */
    protected function compileLower($expression)
    {
        return "<?php strtolower($expression) ?>";
    }
}
