<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesJson
{
    /**
     * Compile the PHP statement into encoded JSON with double-quoted strings.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJson($expression)
    {
        return "<?php echo Illuminate\Support\Json::encode($expression) ?>";
    }

    /**
     * Compile the PHP statement into encoded JSON with double-quoted strings.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJsonEncode($expression)
    {
        return "<?php echo Illuminate\Support\Json::encode($expression) ?>";
    }

    /**
     * Compile a PHP expression into a JavaScript object, array or single-quoted string.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJsonParse($expression)
    {
        return "<?php echo Illuminate\Support\Json::parse($expression) ?>";
    }

    /**
     * Compile a PHP boolean into JavaScript true/false.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJsonBool($expression)
    {
        return "<?php echo Illuminate\Support\Json::bool($expression) ?>";
    }

    /**
     * Compile a PHP string into JavaScript single-quoted string.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJsonStr($expression)
    {
        return "<?php echo Illuminate\Support\Json::str($expression) ?>";
    }
}
