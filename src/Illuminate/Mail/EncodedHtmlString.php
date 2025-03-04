<?php

namespace Illuminate\Mail;

use Illuminate\Support\HtmlString;

class EncodedHtmlString extends HtmlString
{
    /**
     * The callback that should be used to encode the html strings.
     *
     * @var callable|null
     */
    protected static $encodeUsingFactory;

    /**
     * Convert using default encoding.
     *
     * @internal
     *
     * @param  string|null  $value
     * @param  int  $withQuote
     * @param  bool  $doubleEncode
     * @return string
     */
    public static function convert($value, bool $withQuote = true, bool $doubleEncode = true)
    {
        $flag = $withQuote ? ENT_QUOTES : ENT_NOQUOTE;

        return htmlspecialchars($value ?? '', $flag | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }

    /**
     * Get the HTML string.
     *
     * @return string
     */
    #[\Override]
    public function toHtml()
    {
        return (static::$encodeUsingFactory ?? function ($value) {
            return static::convert($value);
        })($this->html);
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
