<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Support\Str;

trait CompilesSnippets
{
    /**
     * Compile the snippet statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileSnippet($expression)
    {
        [$function, $args] = $this->extractSnippetParts($expression);

        return implode("\n", [
            "<?php if (! isset(\${$function})):",
            '$'.$function.' = static function('.$args.') use($__env) {',
            '?>',
        ]);
    }

    /**
     * Compile the endsnippet statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndSnippet($expression)
    {
        return implode("\n", [
            '<?php } ?>',
            '<?php endif; ?>',
        ]);
    }

    /**
     * Compile the renderSnippet statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileRenderSnippet($expression)
    {
        [$function, $args] = $this->extractSnippetParts($expression);

        return '<?php echo $'.$function.'('.$args.'); ?>';
    }

    /**
     * Analyse the snippet expression and extract the function and optional arguments.
     *
     * @param  string  $expression
     * @return array
     */
    protected function extractSnippetParts($expression)
    {
        $functionParts = explode(',', $this->stripParentheses($expression));

        $function = trim(array_shift($functionParts), "'\" ");

        if (empty($function)) {
            $function = 'function';
        }

        $function = '__snippet_'.Str::camel($function);

        $args = trim(implode(',', $functionParts));

        return [$function, $args];
    }
}
