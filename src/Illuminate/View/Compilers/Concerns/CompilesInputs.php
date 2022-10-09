<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesInputs
{
    /**
     * Compile the value of 'input' or 'select' HTML elements.
     *
     * @param $value
     * @return string
     */
    protected function compileValue($value)
    {
        $expression = $this->stripParentheses($value);

        return "<?php echo 'value=\"' . $expression . '\"'; ?>";
    }
}
