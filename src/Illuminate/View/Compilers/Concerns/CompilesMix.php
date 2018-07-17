<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesMix
{
    /**
     * Get the path to a versioned Mix file.
     *
     * @param  string  $path
     * @param  string  $manifestDirectory
     * @return \Illuminate\Support\HtmlString|string
     *
     * @throws \Exception
     */
    protected function compileMix($arguments)
    {
        return "<?php echo mix$arguments; ?>";
    }
}
