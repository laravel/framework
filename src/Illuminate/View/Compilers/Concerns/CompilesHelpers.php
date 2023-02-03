<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Foundation\Vite;

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

    /**
     * Compile the "dd" statements into valid PHP.
     *
     * @param  string  $arguments
     * @return string
     */
    protected function compileDd($arguments)
    {
        return "<?php dd{$arguments}; ?>";
    }

    /**
     * Compile the "dump" statements into valid PHP.
     *
     * @param  string  $arguments
     * @return string
     */
    protected function compileDump($arguments)
    {
        return "<?php dump{$arguments}; ?>";
    }

    /**
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
     * Compile the PUT method statements into valid PHP.
     *
     * @param  string  $arguments
     * @return string
     */
    protected function compilePut($arguments)
    {
        if ($arguments === null) {
            return $this->compileMethod("('PUT')");
        }

        return 'method="POST" action="<?php echo Illuminate\\Support\\Str::of'.$arguments.'->whenContains("?", fn ($url) => $url->append("&_method=PUT"), fn ($url) => $url->append("?_method=PUT"))->toString(); ?>"';
    }

    /**
     * Compile the PATCH method statements into valid PHP.
     *
     * @param  string  $arguments
     * @return string
     */
    protected function compilePatch($arguments)
    {
        if ($arguments === null) {
            return $this->compileMethod("('PATCH')");
        }

        return 'method="POST" action="<?php echo Illuminate\\Support\\Str::of'.$arguments.'->whenContains("?", fn ($url) => $url->append("&_method=PATCH"), fn ($url) => $url->append("?_method=PATCH"))->toString(); ?>"';
    }

    /**
     * Compile the DELETE method statements into valid PHP.
     *
     * @param  string  $arguments
     * @return string
     */
    protected function compileDelete($arguments)
    {
        if ($arguments === null) {
            return $this->compileMethod("('DELETE')");
        }

        return 'method="POST" action="<?php echo Illuminate\\Support\\Str::of'.$arguments.'->whenContains("?", fn ($url) => $url->append("&_method=DELETE"), fn ($url) => $url->append("?_method=DELETE"))->toString(); ?>"';
    }

    /**
     * Compile the "vite" statements into valid PHP.
     *
     * @param  string|null  $arguments
     * @return string
     */
    protected function compileVite($arguments)
    {
        $arguments ??= '()';

        $class = Vite::class;

        return "<?php echo app('$class'){$arguments}; ?>";
    }

    /**
     * Compile the "viteReactRefresh" statements into valid PHP.
     *
     * @return string
     */
    protected function compileViteReactRefresh()
    {
        $class = Vite::class;

        return "<?php echo app('$class')->reactRefresh(); ?>";
    }
}
