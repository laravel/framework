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
        $parts = explode(',', $this->stripParentheses($expression));

        $options = isset($parts[1]) ? trim($parts[1]) : $this->encodingOptions;

        $depth = isset($parts[2]) ? trim($parts[2]) : 512;

        $wrapped = $this->wrapJsonHandler($parts[0], $options, $depth);

        return "<?php echo $wrapped ?>";
    }

    /**
     * Wraps the given value in a json_encode function call.
     *
     * @param  string     $value
     * @param  int        $options
     * @param  int|string $depth
     * @return string
     */
    protected function wrapJsonHandler($value, $options, $depth)
    {
        $value = "json_encode($value, $options, $depth)";
        return sprintf($this->echoFormat, $value);
    }
}
