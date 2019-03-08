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
        $expression = '<?php ' . $this->stripParentheses($expression);

        $tokens = token_get_all($expression);

        $openExpressions = 0;

        $arguments = [
            null,
            null,
            null,
        ];

        $currentArgument = 0;

        //remove the first
        unset($tokens[0]);

        foreach ($tokens as $token) {

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

        $output = [
            trim($arguments[0]),
            trim($arguments[1] ?? $this->encodingOptions),
            trim($arguments[2] ?? 512),
        ];

        return "<?php echo json_encode($output[0], $output[1], $output[2]) ?>";
    }
}
