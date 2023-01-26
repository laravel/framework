<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesArray
{
    /**
     * @param  string  $arguments
     * @return string
     */
    protected function compileSum($arguments)
    {
        $arguments = str_replace(['[', ']'], '', $arguments);
        $arguments = explode(',', $arguments);

        return "<?php echo array_sum($arguments) ?>";
    }
}
