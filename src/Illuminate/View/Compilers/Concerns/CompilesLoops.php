<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Contracts\View\ViewCompilationException;

trait CompilesLoops
{
    /**
     * Counter to keep track of nested forelse statements.
     *
     * @var int
     */
    protected $forElseCounter = 0;

    /**
     * Compile the for-else statements into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     *
     * @throws \Illuminate\Contracts\View\ViewCompilationException
     */
    protected function compileForelse($expression)
    {
        $empty = '$__empty_'.++$this->forElseCounter;

        preg_match('/\( *(.+) +as +(.+)\)$/is', $expression ?? '', $matches);

        if (count($matches) === 0) {
            throw new ViewCompilationException('Malformed @forelse statement.');
        }

        $iteratee = trim($matches[1]);

        $iteration = trim($matches[2]);

        $commenceLoop = "\$__env->addLoop({$iteratee})";

        $assignLoop = '$loop = $__env->getLastLoop();';

        return "<?php {$empty} = true; foreach({$commenceLoop} as {$iteration}): {$assignLoop} {$empty} = false; ?>";
    }

    /**
     * Compile the for-else-empty and empty statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEmpty($expression)
    {
        if ($expression) {
            return "<?php if(empty{$expression}): ?>";
        }

        $empty = '$__empty_'.$this->forElseCounter--;

        return "<?php \$__env->incrementLoopIndices(); endforeach; \$loop = \$__env->popLoop(); if ({$empty}): ?>";
    }

    /**
     * Compile the end-for-else statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndforelse()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-empty statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndEmpty()
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the for statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileFor($expression)
    {
        return "<?php for{$expression}: ?>";
    }

    /**
     * Compile the for-each statements into valid PHP.
     *
     * @param  string|null  $expression
     * @return string
     *
     * @throws \Illuminate\Contracts\View\ViewCompilationException
     */
    protected function compileForeach($expression)
    {
        preg_match('/\( *(.+) +as +(.*)\)$/is', $expression ?? '', $matches);

        if (count($matches) === 0) {
            throw new ViewCompilationException('Malformed @foreach statement.');
        }

        $iteratee = trim($matches[1]);

        $iteration = trim($matches[2]);

        $commenceLoop = "\$__env->addLoop({$iteratee})";

        $assignLoop = '$loop = $__env->getLastLoop();';

        return "<?php foreach({$commenceLoop} as {$iteration}): {$assignLoop} ?>";
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileBreak($expression)
    {
        if ($expression) {
            preg_match('/\(\s*(-?\d+)\s*\)$/', $expression, $matches);

            return $matches ? '<?php break '.max(1, $matches[1]).'; ?>' : "<?php if{$expression} break; ?>";
        }

        return '<?php break; ?>';
    }

    /**
     * Compile the continue statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileContinue($expression)
    {
        if ($expression) {
            preg_match('/\(\s*(-?\d+)\s*\)$/', $expression, $matches);

            return $matches ? '<?php continue '.max(1, $matches[1]).'; ?>' : "<?php if{$expression} continue; ?>";
        }

        return '<?php continue; ?>';
    }

    /**
     * Compile the end-for statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndfor()
    {
        return '<?php endfor; ?>';
    }

    /**
     * Compile the end-for-each statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndforeach()
    {
        return '<?php $__env->incrementLoopIndices(); endforeach; $loop = $__env->popLoop(); ?>';
    }

    /**
     * Compile the while statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileWhile($expression)
    {
        return "<?php while{$expression}: ?>";
    }

    /**
     * Compile the end-while statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndwhile()
    {
        return '<?php endwhile; ?>';
    }
}
