<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesRoutes
{
    /**
     * Compile the routeLink statements into valid PHP.
     *
     * Usage: @routeLink('home')
     */
    protected function compileRouteLink($expression)
    {
        return "<?php echo '<a href=\"'.route($expression).'\">'.routeLabel($expression).'</a>'; ?>";
    }
}
