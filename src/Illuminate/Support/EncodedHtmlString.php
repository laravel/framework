<?php

namespace Illuminate\Support;

class EncodedHtmlString extends HtmlString
{
    /**
     * The callback that should be used to encode the html strings.
     *
     * @var callable|null
     */
    protected static $encodeUsingFactory;

    /**
     * Create a new Encoded HTML string instance.
     *
     * @param  string  $html
     * @param  bool  $doubleEncode
     * @return void
     */
    public function __construct($html = '', protected bool $doubleEncode = true)
    {
        parent::__construct($html);
    }

    /**
     * Convert using default encoding.
     *
     * @internal
     *
     * @param  string|null  $value
     * @param  int  $flag
     * @param  string  $encoding
     * @param  bool  $doubleEncode
     * @return string
     */
    public static function convert($value, int $flag = ENT_QUOTES | ENT_SUBSTITUTE, string $encoding = 'UTF-8', bool $doubleEncode = true)
    {
        return htmlspecialchars($value ?? '', $flag, $encoding, $doubleEncode);
    }

    /**
     * Get the HTML string.
     *
     * @return string
     */
    #[\Override]
    public function toHtml()
    {
        return (static::$encodeUsingFactory ?? function ($value, bool $doubleEncode) {
            return static::convert($value, doubleEncode: $doubleEncode);
        })($this->html, $this->doubleEncode);
    }

    /**
     * Set the callable that will be used to encode the html strings.
     *
     * @param  callable|null  $factory
     * @return void
     */
    public static function encodeUsing(?callable $factory = null)
    {
        static::$encodeUsingFactory = $factory;
    }

    /**
     * Flush the class's global state.
     *
     * @return void
     */
    public static function flushState()
    {
        static::$encodeUsingFactory = null;
    }
}
