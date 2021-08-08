<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Support\Str;

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
        $expression = $this->stripParentheses($expression);
        $hasMultipleErrors = Str::startsWith($expression, '[');

        $conditionFunc = $hasMultipleErrors ? 'hasAny' : 'has';
        $condition = 'if ($__bag->' . $conditionFunc . '($__errorArgs[0])) :';

        return '<?php $__errorArgs = [' . $expression . '];
$__bag = $errors->getBag($__errorArgs[1] ?? \'default\');
' . $condition . '
if (isset($message)) { $__messageOriginal = $message; }
if (isset($messages)) { $__messagesOriginal = $messages; }
' . ($hasMultipleErrors

? '$messages = array_reduce($__errorArgs[0], function($carry, $__error) use($__bag) {
    $newline = $__bag->first($__error);
    if($newline) $carry[] = $newline;
    return $carry;
}, []);
$message = implode('. ', $messages); ?>'
            
: '$message = $__bag->first($__errorArgs[0]); ?>'
        );
    }

    /**
     * Compile the enderror statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEnderror($expression)
    {
        return '<?php unset($message, $messages);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
if (isset($__messagesOriginal)) { $messages = $__messagesOriginal; }
endif;
unset($__errorArgs, $__bag); ?>';
    }
}
