<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesValidations
{
    /**
     * Compile the if-errorshas statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileErrorshas($expression)
    {
        return "<?php if(\$errors->has{$expression}): ?>";
    }

    /**
     * Compile the end-errorshas statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndErrorshas()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the if-errorsany statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileErrorsany($expression)
    {
        return "<?php if(\$errors->hasAny{$expression}): ?>";
    }

    /**
     * Compile the end-errorsany statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndErrorsany()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the errorsfirst statements into valid PHP.
     *
     * @return string
     */
    protected function compileErrorsfirst()
    {
        return '<?php echo $errors->first(\'email\'); ?>';
    }
}
