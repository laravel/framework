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
        $parts = explode(',', $this->stripParentheses($expression));

        $options = $parts[1] ?? 0;

        $depth = $parts[2] ?? 512;

        return "<?php echo json_encode($parts[0], $options, $depth) ?>";
    }
}
