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
     * Compile the method statements into valid PHP.
     *
     * @param  string  $method
     * @return string
     */
    protected function compileMethod($method)
    {
        return "<?php echo method_field{$method}; ?>";
    }

    /*
     * Compile the dump helper statements into valid PHP.
     *
     * @param  string  $args
     * @return string
     */
    protected function compileDump($args)
    {
        return "<?php dump{$args}; ?>";
    }
}
