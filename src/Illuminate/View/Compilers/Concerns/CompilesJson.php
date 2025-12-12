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
        [$data, $options, $depth] = $this->parseArguments($this->stripParenthesis($expression)) + ['null', $this->encodingOptions, 512];

        return "<?php echo json_encode($data, $options, $depth) ?>";
    }

    /**
     * Parse arguments from an expression, respecting nested structures.
     *
     * This method properly handles commas inside arrays, closures, function calls,
     * and other nested structures by using PHP's tokenizer.
     *
     * @param  string  $expression
     * @return array{0?: string, 1?: string, 2?: string}
     */
    protected function parseArguments($expression)
    {
        if ('' === trim($expression)) {
            return [];
        }

        $tokens = @token_get_all('<?php '.$expression);

        if (false === $tokens) {
            // Fallback to simple explode if tokenization fails
            return array_map('trim', explode(',', $expression));
        }

        $parts = [];
        $current = '';
        $depth = 0;

        foreach ($tokens as $index => $token) {
            // Skip the initial <?php token
            if (0 === $index && is_array($token) && T_OPEN_TAG === $token[0]) {
                continue;
            }

            if (is_array($token)) {
                [$id, $text] = $token;

                // Handle strings - preserve them completely
                if (in_array($id, [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE])) {
                    $current .= $text;
                    continue;
                }

                // Handle whitespace at top level when $current is empty (can be ignored)
                if (T_WHITESPACE === $id && 0 === $depth && '' === $current) {
                    continue;
                }

                $current .= $text;
            } else {
                $char = $token;

                // Track nesting depth for parentheses, brackets, and braces
                if ('(' === $char || '[' === $char || '{' === $char) {
                    $depth++;
                    $current .= $char;
                } elseif (')' === $char || ']' === $char || '}' === $char) {
                    $depth--;
                    $current .= $char;
                } elseif (',' === $char && 0 === $depth) {
                    // Only split on commas at the top level
                    $parts[] = trim($current);
                    $current = '';
                } else {
                    $current .= $char;
                }
            }
        }

        // Add the last part
        if ('' !== $current) {
            $parts[] = trim($current);
        }

        return $parts;
    }
}
