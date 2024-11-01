<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Arrayable;

class UrlQueryParameters implements Arrayable
{
    /**
     * Constructor.
     *
     * @param  array  $parameters
     * @return void
     */
    public function __construct(
        protected array $parameters = []
    ) {
    }

    /**
     * Parse a query string.
     *
     * @param  string|null  $query
     * @return static
     */
    public static function parse($query)
    {
        if (is_null($query)) {
            return new static;
        }

        parse_str(
            Str::of(urldecode($query))
                ->replaceStart('?', '')
                ->toString(),
            $params
        );

        return new static($params);
    }

    /**
     * Get a parameter value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Check if a parameter exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->parameters[$key]);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray()
    {
        return $this->parameters;
    }
}
