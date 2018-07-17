<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesMix
{
    /**
     * Get the path to a versioned Mix file.
     *
     * @param  string  $arguments
     * @return string
     */
    protected function compileMix($arguments)
    {
        return "<?php echo mix$arguments; ?>";
    }
}
