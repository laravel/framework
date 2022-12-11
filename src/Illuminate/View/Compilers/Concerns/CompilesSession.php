<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesSession
{
    /**
     * Compile the session statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileSession($expression)
    {
        $expression = $this->stripParentheses($expression);

        return '<?php if (session()->has('.$expression.')) :
if (isset($message)) { $__messageOriginal = $message; }
$message = session()->get('.$expression.'); ?>';
    }

    /**
     * Compile the endsession statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndsession($expression)
    {
        return '<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif; ?>';
    }
}
