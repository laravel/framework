<?php

namespace Illuminate\Support;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Stringable;

class UrlQueryParameters implements Arrayable, ArrayAccess, Stringable
{
    /**
     * Constructor.
     *
     * @param  array<string, string>  $parameters
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
                ->chopStart('?')
                ->toString(),
            $parameters
        );

        return new static($parameters);
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
     * Set a parameter value.
     *
     * @param  string  $key
     * @param  string  $value
     * @return $this
     */
    public function set(string $key, string $value): static
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Remove a parameter.
     *
     * @param  string  $key
     * @return $this
     */
    public function forget(string $key): static
    {
        unset($this->parameters[$key]);

        return $this;
    }

    /**
     * Clear all parameters.
     *
     * @return $this
     */
    public function clear(): static
    {
        $this->parameters = [];

        return $this;
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
     * Get all parameters.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Determine if the parameters are empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->parameters);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return $this->all();
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

    /**
     * Determine if a parameter exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->parameters[$offset]);
    }

    /**
     * Get a parameter value.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->parameters[$offset];
    }

    /**
     * Set a parameter value.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->parameters[$offset] = $value;
    }

    /**
     * Remove a parameter.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->parameters[$offset]);
    }
}
