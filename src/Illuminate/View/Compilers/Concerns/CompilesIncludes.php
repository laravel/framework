<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesIncludes
{
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

        return "<?php echo \$__env->make({$expression}, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>";
    }

    /**
     * Compile the view statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileView($expression)
    {
        $expression = $this->stripParentheses($expression);

        return $this->compileInclude($expression);
    }

    /**
     * Compile the view-exist statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileViewExist($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php if (\$__env->exists({$expression})): ?>";
    }

    /**
     * Compile the end-view-exist statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileEndViewExist()
    {
        return '<?php endif; ?>';
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

        return $this->compileViewExist($expression).$this->compileInclude($expression).$this->compileEndViewExist();
    }

    /**
     * Compile the include-when statements into valid PHP.
     *
     * @param string $expression
     * @return string
     */
    protected function compileIncludeWhen($expression)
    {
        $expression = $this->stripParentheses($expression);

        return "<?php echo \$__env->renderWhen($expression, array_except(get_defined_vars(), array('__data', '__path'))); ?>";
    }
}
