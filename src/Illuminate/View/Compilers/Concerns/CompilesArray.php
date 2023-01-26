<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesArray
{
    /**
     *  Compile the "array_sum" statements into valid PHP.
     *
     * @param  string  $arguments
     * @return string
     */
    protected function compileSum($arguments)
    {
        $arguments = str_replace(['[', ']'], '', $arguments);
        $arguments = explode(', ', $arguments);
        $sum = array_sum($arguments);

        return "<?php echo $sum; ?>";
    }
}
