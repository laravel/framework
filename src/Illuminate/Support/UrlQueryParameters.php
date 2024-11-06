<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Arrayable;
use Stringable;

class UrlQueryParameters implements Arrayable, Stringable
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
    public static function parse(?string $query): static
    {
        if (is_null($query)) {
            return new static;
        }

        parse_str(
            Str::of($query)
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
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Check if a parameter exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->parameters[$key]);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->parameters;
    }

    /**
     * Convert the query parameters to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Arr::query($this->parameters);
    }
}
