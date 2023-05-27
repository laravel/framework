<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesAttributes
{
    /**
     * Compile the attributes statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileAttributes($expression)
    {
        $expression = is_null($expression) ? '([])' : $expression;

        return "<?php echo (new \Illuminate\View\ComponentAttributeBag)$expression; ?>";
    }
}
