<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesJson
{
    /**
     * Default encoding options.
     *
     * To make JSON safe for embedding into HTML, <, >, ', &, and " characters
     * should be escaped.
     *
     * @var int
     */
    private $safeEncodingOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    /**
     * Compile the JSON statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJson($expression)
    {
        $parts = explode(',', $this->stripParentheses($expression));

        $options = trim($parts[1] ?? $this->safeEncodingOptions);
        $depth = trim($parts[2] ?? 512);

        return "<?php echo json_encode($parts[0], $options, $depth) ?>";
    }
}
