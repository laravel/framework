<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesIncludes
{
    /**
     * Compile the inline statements into valid PHP.
     *
     * Reads the partial's Blade source at compile time, strips any @props
     * directive, compiles it, and embeds the resulting PHP directly into
     * the parent view — eliminating view factory overhead at runtime.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileInline($expression)
    {
        $expression = $this->stripParentheses($expression);

        $str = \Illuminate\Support\Str::of($expression);

        // The view name must be a string literal — extract it from quotes.
        $view = $str->before(',')->trim('\'" ')->toString();

        // Resolve the view file path through the view finder.
        $finder = \Illuminate\Container\Container::getInstance()->make('view.finder');
        $path = $finder->find($view);

        $source = $this->files->get($path);

        // Strip @props — not needed for inlined partials since variables
        // come from the parent scope. Avoids ComponentAttributeBag overhead.
        $source = preg_replace('/@props\s*\([\s\S]*?\)\s*\n?/', '', $source);

        $compiled = $this->compileString($source);

        // If a data array was passed, emit an extract() call so the
        // array values become local variables at runtime.
        if ($str->contains(',')) {
            $data = $str->after(',')->trim()->toString();

            $compiled = "<?php extract({$data}); ?>".$compiled;
        }

        return $compiled;
    }


    /**
     * Compile the each statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEach($expression)
    {
        return "<?php echo \$__env->renderEach{$expression}; ?>";
    }

    /**
     * Compile the include statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileInclude($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->make({$expression}, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>";
    }

    /**
     * Compile the include-if statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIncludeIf($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php if (\$__env->exists({$expression})) echo \$__env->make({$expression}, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>";
    }

    /**
     * Compile the include-when statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIncludeWhen($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->renderWhen($expression, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1])); ?>";
    }

    /**
     * Compile the include-unless statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIncludeUnless($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->renderUnless($expression, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1])); ?>";
    }

    /**
     * Compile the include-first statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIncludeFirst($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->first({$expression}, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>";
    }

    /**
     * Compile the include-isolated statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIncludeIsolated($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->make({$expression})->render(); ?>";
    }
}
