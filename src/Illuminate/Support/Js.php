<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class Js implements Htmlable
{
    /**
     * The JavaScript string.
     *
     * @var string
     */
    protected $js;

    /**
     * Flags that should be used when encoding to JSON.
     *
     * @var int
     */
    protected const REQUIRED_FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;

    /**
     * Create a new class instance.
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
     * Create a new JavaScript string from the given data.
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
     * Convert the given data to a JavaScript expression.
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
     * Encode the given data as JSON.
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
     * Convert the given JSON to a JavaScript expression.
     *
     * @param  string  $json
     * @param  int  $flags
     * @return string
     *
     * @throws \JsonException
     */
    protected function convertJsonToJavaScriptExpression($json, $flags = 0)
    {
        if ($json === '[]' || $json === '{}') {
            return $json;
        }

        if (Str::startsWith($json, ['"', '{', '['])) {
            return "JSON.parse('".substr(json_encode($json, $flags | static::REQUIRED_FLAGS), 1, -1)."')";
        }

        return $json;
    }

    /**
     * Get the string representation of the data for use in HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->js;
    }

    /**
     * Get the string representation of the data for use in HTML.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toHtml();
    }
}
