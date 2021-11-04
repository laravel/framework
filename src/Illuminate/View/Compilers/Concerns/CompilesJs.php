<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Support\JsString;

trait CompilesJs
{
    /**
     * Compile the @js() directive into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJs(string $expression)
    {
        return sprintf(
            "<?php echo \%s::from(%s)->toHtml() ?>",
            JsString::class, $this->stripParentheses($expression)
        );
    }
}
