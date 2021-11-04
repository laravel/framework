<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class JsString implements Htmlable
{
    /**
     * Flags that must always be used when encoding to JSON for JsString.
     *
     * @var int
     */
    protected const REQUIRED_FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;

    /**
     * The javascript string.
     *
     * @var string
     */
    protected $js;

    /**
     * Create a new JsString from data.
     *
     * @param  mixed  $data
     * @param  int  $flags
     * @param  int  $depth
     * @return static
     *
     * @throws \JsonException
     */
    public static function from($data, $flags = 0, $depth = 512)
    {
        return new static($data, $flags, $depth);
    }

    /**
     * Create a new JsString.
     *
     * @param  mixed  $data
     * @param  int|null  $flags
     * @param  int  $depth
     * @return void
     *
     * @throws \JsonException
     */
    public function __construct($data, $flags = 0, $depth = 512)
    {
        $this->js = $this->convertDataToJavaScriptExpression($data, $flags, $depth);
    }

    /**
     * Get string representation of data for use in HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->js;
    }

    /**
     * Get string representation of data for use in HTML.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toHtml();
    }

    /**
     * Convert data to a JavaScript expression.
     *
     * @param  mixed  $data
     * @param  int  $flags
     * @param  int  $depth
     * @return string
     *
     * @throws \JsonException
     */
    protected function convertDataToJavaScriptExpression($data, $flags = 0, $depth = 512)
    {
        if ($data instanceof self) {
            return $data->toHtml();
        }

        $json = $this->jsonEncode($data, $flags, $depth);

        if (is_string($data)) {
            return "'".substr($json, 1, -1)."'";
        }

        return $this->convertJsonToJavaScriptExpression($json, $flags);
    }

    /**
     * Encode data as JSON.
     *
     * @param  mixed  $data
     * @param  int  $flags
     * @param  int  $depth
     * @return string
     *
     * @throws \JsonException
     */
    protected function jsonEncode($data, $flags = 0, $depth = 512)
    {
        if ($data instanceof Jsonable) {
            return $data->toJson($flags | static::REQUIRED_FLAGS);
        }

        if ($data instanceof Arrayable && ! ($data instanceof JsonSerializable)) {
            $data = $data->toArray();
        }

        return json_encode($data, $flags | static::REQUIRED_FLAGS, $depth);
    }

    /**
     * Convert JSON to a JavaScript expression.
     *
     * @param  string  $json
     * @param  int  $flags
     * @return string
     *
     * @throws \JsonException
     */
    protected function convertJsonToJavaScriptExpression($json, $flags = 0)
    {
        if ('[]' === $json || '{}' === $json) {
            return $json;
        }

        if (Str::startsWith($json, ['"', '{', '['])) {
            $json = json_encode($json, $flags | static::REQUIRED_FLAGS);

            return "JSON.parse('".substr($json, 1, -1)."')";
        }

        return $json;
    }
}
