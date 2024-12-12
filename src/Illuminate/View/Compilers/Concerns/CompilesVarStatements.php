<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesVarStatements
{
    /**
     * Compile the var statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileVar($expression)
    {
        $var = preg_replace("/[()]/", '', $expression);

        $var = ltrim(trim($var, " '\""), '\\');

        return "<?php /** @var \\{$var} */ ?>";
    }
}
