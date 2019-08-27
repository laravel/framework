<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesErrors
{
    /**
     * Compile the error statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileError($expression)
    {
        $expression = $this->stripParentheses($expression);

        return '<?php $__args = ['.$expression.'];
$__bag = $errors->getBag($__args[1] ?? \'default\');
if ($__bag->has($__args[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__args[0]); ?>';
    }

    /**
     * Compile the enderror statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEnderror($expression)
    {
        return '<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__args, $__bag); ?>';
    }
}
