<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesJson
{
    /**
     * The default JSON encoding options.
     *
     * @var int
     */
    private $encodingOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    /**
     * Compile the JSON statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJson($expression)
    {
        $tokens = token_get_all('<?php ' . $this->stripParentheses($expression));

        $openExpressions = 0;

        $arguments = [null, null, null];

        $currentArgument = 0;

        foreach (array_slice($tokens, 1) as $token) {

            //increment if we have an opening character
            if (is_string($token) && in_array($token, ['(', '['])) {
                $openExpressions++;
            }

            //decrement if we have a closing character
            elseif (is_string($token) && in_array($token, [')', ']'])) {
                $openExpressions--;
            }

            //we have no open expressions, and the token is a comma, move to the next argument
            if ($openExpressions === 0 && is_string($token) && $token === ',') {
                $currentArgument++;
            }

            elseif (is_array($token)) {
                $arguments[$currentArgument] .= $token[1];
            }

            else {
                $arguments[$currentArgument] .= $token;
            }
        }

        $value = trim($arguments[0]);
        $options = trim($arguments[1] ?? $this->encodingOptions);
        $depth = trim($arguments[2] ?? 512);

        return "<?php echo json_encode($value, $options, $depth) ?>";
    }
}
