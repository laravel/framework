<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesRouteHelpers
{
    /**
     * Compile the if-else-route-is statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIfRouteIs($expression)
    {
        $args = $this->stripParentheses($expression);
        [$name, $true] = array_map('trim', explode(',', $args));

        return "<?php if(request()->routeIs($name)) { echo $true; } ?>";
    }

    /**
     * Compile the if-route-is statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIfElseRouteIs($expression)
    {
        $args = $this->stripParentheses($expression);
        [$name, $true, $false ] = array_map('trim', explode(',', $args));

        return "<?php if(request()->routeIs($name)) { echo $true; } else { echo $false; } ?>";
    }

    /**
     * Compile the route statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileRoute($expression)
    {
        return "<?php echo route{$expression}; ?>";
    }
}
