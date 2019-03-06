<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesJson
{
    /**
     * Compile the JSON statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJson($expression)
    {
        return "<?php echo json_encode(" . $this->stripParentheses($expression) . ") ?>";
    }
}
