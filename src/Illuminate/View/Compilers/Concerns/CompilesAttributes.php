<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Support\HtmlString;
use Illuminate\View\HtmlAttributes;

trait CompilesAttributes
{
    /**
     * Conditionally compile HTML attributes
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileAttributes($expression)
    {
        $expression = $this->stripParentheses($expression);
        $parts = eval("return [$expression];");

        $attributes = $parts[0];
        $constraints = null;
        $escape = true;

        if (is_array($parts[0]) && isset($parts[1]) && !isset($parts[2])) {
            $escape = $parts[1];
        }
        if (!is_array($parts[0])) {
            if (isset($parts[1], $parts[2])) {
                [$constraints, $escape] = [$parts[1], $parts[2]];
            } else {
                $constraints = $parts[1];
            }
        }

        $getAttributes = new HtmlAttributes($attributes, $constraints, $escape);

        return "<?php echo '$getAttributes'; ?>";
    }
}
