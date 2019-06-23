<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Support\Arr;

trait CompilesErrors
{
    /**
     * Compile the error statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileError($expression)
    {
        $expression = explode(',', $this->stripParentheses($expression));
        $attribute = trim(Arr::get($expression, 0));
        $bag = trim(Arr::get($expression, 1, '\'default\''));

        return '<?php if ($errors->getBag('.$bag.')->has('.$attribute.')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->getBag('.$bag.')->first('.$attribute.'); ?>';
    }

    /**
     * Compile the enderror statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEnderror($expression)
    {
        return '<?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>';
    }
}
