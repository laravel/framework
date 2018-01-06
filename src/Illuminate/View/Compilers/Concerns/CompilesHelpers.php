<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesHelpers
{
    /**
     * Compile the CSRF statements into valid PHP.
     *
     * @return string
     */
    protected function compileCsrf()
    {
        return '<?php echo csrf_field(); ?>';
    }

    /*
     * Compile the "dd" statements into valid PHP.
     *
     * @param  string  $arguments
     * @return string
     */
    protected function compileDd($arguments)
    {
        return "<?php dd{$arguments}; ?>";
    }

    /*
     * Compile the method statements into valid PHP.
     *
     * @param  string  $method
     * @return string
     */
    protected function compileMethod($method)
    {
        return "<?php echo method_field{$method}; ?>";
    }

    /**
     * Compile the hasError statements into valid PHP.
     *
     * @param  string  $field
     * @return string
     */
    protected function compileHasError($field)
    {
        return "<?php if (\$errors->has{$field}): ?>";
    }

    /**
     * Compile the end-hasError statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndHasError()
    {
        return '<?php endif; ?>';
    }
}
