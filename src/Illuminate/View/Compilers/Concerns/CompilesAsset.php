<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesAsset
{
    /**
     * Generate an asset path for the application.
     *
     * @param  string  $path
     * @param  bool    $secure
     * @return string
     */
    protected function compileAsset($arguments)
    {
        return "<?php echo asset$arguments; ?>";
    }
}
