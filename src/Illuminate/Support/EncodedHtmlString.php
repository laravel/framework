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
     * @return void
     */
    public function __construct($html = '', protected bool $doubleEncode = true)
    {
        parent::__construct($html);
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
            return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
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
