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
        $parsed = $this->parseJsonExpression($expression);

        $options = $parsed['options'] ?? $this->encodingOptions;
        $depth = $parsed['depth'] ?? 512;

        return "<?php echo json_encode({$parsed['data']}, $options, $depth) ?>";
    }

    /**
     * Parse the JSON expression to handle nested parentheses and closures.
     *
     * @param  string  $expression
     * @return array
     */
    protected function parseJsonExpression($expression)
    {
        $expression = trim($expression);

        if (! str_starts_with($expression, '(') || ! str_ends_with($expression, ')')) {
            return ['data' => $expression, 'options' => null, 'depth' => null];
        }

        // Remove outer parentheses
        $content = substr($expression, 1, -1);

        // Find the main data expression by counting parentheses and brackets
        $data = '';
        $options = null;
        $depth = null;

        $parenCount = 0;
        $bracketCount = 0;
        $inString = false;
        $stringChar = null;
        $i = 0;
        $length = strlen($content);

        while ($i < $length) {
            $char = $content[$i];

            // Handle string literals
            if (! $inString && ($char === "'" || $char === '"')) {
                $inString = true;
                $stringChar = $char;
                $data .= $char;
            } elseif ($inString && $char === $stringChar) {
                // Check for escaped quotes
                if ($i > 0 && $content[$i - 1] === '\\') {
                    $data .= $char;
                } else {
                    $inString = false;
                    $stringChar = null;
                    $data .= $char;
                }
            } elseif ($inString) {
                $data .= $char;
            } else {
                // Handle parentheses and brackets outside strings
                if ($char === '(') {
                    $parenCount++;
                    $data .= $char;
                } elseif ($char === ')') {
                    $parenCount--;
                    $data .= $char;
                } elseif ($char === '[') {
                    $bracketCount++;
                    $data .= $char;
                } elseif ($char === ']') {
                    $bracketCount--;
                    $data .= $char;
                } elseif ($char === ',' && $parenCount === 0 && $bracketCount === 0) {
                    // Found a comma at the top level - this separates data from options
                    $remaining = trim(substr($content, $i + 1));
                    $parts = explode(',', $remaining);

                    if (isset($parts[0])) {
                        $options = trim($parts[0]);
                    }
                    if (isset($parts[1])) {
                        $depth = trim($parts[1]);
                    }
                    break;
                } else {
                    $data .= $char;
                }
            }

            $i++;
        }

        return [
            'data' => trim($data),
            'options' => $options,
            'depth' => $depth,
        ];
    }
}
