<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesJson
{
    /**
     * Compile the JSON statement into valid PHP.
     *
     * @param  string  $expression
     * @param  int  $options
     * @param  int  $depth
     * @return string
     */
    protected function compileJson($expression, $options = 0, $depth = 512)
    {
        return "<?php echo json_encode($expression, $options, $depth) ?>";
    }
}
