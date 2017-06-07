<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesClassNames
{
    protected function compileClass($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo 'class=\"'.implode(' ', array_toggled_values({$expression})).'\"'; ?>";
    }
}
