<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesAuthorizations
{
    /**
     * Compile the can statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileCan($expression)
    {
        return "<?php if (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->check{$expression}): ?>";
    }

    /**
     * Compile the canAny statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileCanAny($expression)
    {
        return "<?php if (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->checkAny{$expression}): ?>";
    }

    /**
     * Compile the canEvery statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileCanEvery($expression)
    {
        return "<?php if (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->checkEvery{$expression}): ?>";
    }

    /**
     * Compile the cannot statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileCannot($expression)
    {
        return "<?php if (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->denies{$expression}): ?>";
    }

    /**
     * Compile the else-can statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElsecan($expression)
    {
        return "<?php elseif (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->check{$expression}): ?>";
    }

    /**
     * Compile the else-canAny statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElsecanAny($expression)
    {
        return "<?php elseif (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->checkAny{$expression}): ?>";
    }

    /**
     * Compile the else-canEvery statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElsecanEvery($expression)
    {
        return "<?php elseif (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->checkEvery{$expression}): ?>";
    }

    /**
     * Compile the else-cannot statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElsecannot($expression)
    {
        return "<?php elseif (app(\Illuminate\\Contracts\\Auth\\Access\\Gate::class)->denies{$expression}): ?>";
    }

    /**
     * Compile the end-can statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndcan()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-canAny statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndcanAny()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-canEvery statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndcanEvery()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-cannot statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndcannot()
    {
        return '<?php endif; ?>';
    }
}
