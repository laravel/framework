<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesSessions {
    /**
     * Compile get session of a value defined by argument $expression, while also include its default value
     *
     * @param  string  $expression
     * @return any
     */
    protected function compilesession($expression) {
        $expression = str_replace(["(", ")"], "", $expression);
        $expression = explode(",", $expression);
        $first = $expression[0];
        $second = @$expression[1];
        return "<?php echo session()->get({$first},{$second}); ?>";
    }

    /**
     * Compile pulling session of a value defined by argument $expression,
     * and also include its default value if value is null
     *
     * @param  string  $expression
     * @return any
     */
    protected function compilePullsession($expression) {
        $expression = str_replace(["(", ")"], "", $expression);
        $expression = explode(",", $expression);
        $first = $expression[0];
        $second = @$expression[1];
        return "<?php echo session()->pull({$first},{$second}); ?>";
    }
    

    /**
     * Compile has session statement to check if session is exist
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileHasSession($expression)
    {
        return "<?php if (session()->has({$expression})): ?>";
    }

    /**
     * Compile missing session statement to check if session is not exist
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileMissingSession($expression)
    {
        return "<?php if (!session()->has({$expression})): ?>";
    }

    /**
     * Appropriately finish off has session statement check
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndhassession() {
        return "<?php endif; ?>";
    }

    /**
     * Appropriately finish off missing session statement check
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndmissingsession() {
        return "<?php endif; ?>";
    }
}