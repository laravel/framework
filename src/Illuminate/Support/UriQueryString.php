<?php

namespace Illuminate\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\InteractsWithData;
use League\Uri\QueryString;

class UriQueryString implements Arrayable
{
    use InteractsWithData;

    /**
     * Query string parameters.
     */
    protected array $data;

    /**
     * Create a new URI query string instance.
     */
    public function __construct(Stringable|string|array|null $data = [])
    {
        $this->data = is_array($data) ? $data : static::parse($data);
    }

    /**
     * Create a new URI Query String instance.
     */
    public static function of(Stringable|string|array $query = []): static
    {
        return new static($query);
    }

    /**
     * Parse the given query string.
     */
    public static function parse(Stringable|string|null $query): array
    {
        return QueryString::extract($query);
    }

    /**
     * Retrieve all data from the instance.
     *
     * @param  array|mixed|null  $keys
     * @return array
     */
    public function all($keys = null)
    {
        $query = $this->toArray();

        if (! $keys) {
            return $query;
        }

        $results = [];

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            $results[$key] = $query[$key] ?? null;
        }

        return $results;
    }

    /**
     * Retrieve data from the instance.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function data($key = null, $default = null)
    {
        return $this->get($key, $default);
    }

    /**
     * Get a query string parameter.
     */
    public function get(?string $key = null, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : value($default);
    }

    /**
     * Determine if the data contains a given key.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        $data = $this->toArray();

        foreach ($keys as $value) {
            if (! array_key_exists($value, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the query string.
     */
    public function set(array $value): void
    {
        $this->data = $value;
    }

    /**
     * Get the URL decoded version of the query string.
     */
    public function decode(): string
    {
        return rawurldecode($this->value());
    }

    /**
     * Merge new query parameters into the query string.
     */
    public function merge(array $query): array
    {
        $results = $this->all();

        foreach ($query as $key => $value) {
            $results[$key] = $value;
        }

        $this->set($results);

        return $results;
    }

    /**
     * Merge new query parameters if they are not already in the query string.
     */
    public function mergeIfMissing(array $query): array
    {
        foreach ($query as $key => $value) {
            if ($this->has($key)) {
                unset($query[$key]);
            }
        }

        return $this->merge($query);
    }

    /**
     * Push a value onto the query string.
     */
    public function push(string $key, mixed $value): array
    {
        $currentValue = $this->get($key);

        $values = Arr::wrap($value);

        return $this->merge([$key => match (true) {
            is_array($currentValue) && array_is_list($currentValue) => array_values(array_unique([...$currentValue, ...$values])),
            is_array($currentValue) => [...$currentValue, ...$values],
            ! is_null($currentValue) => [$currentValue, ...$values],
            default => $values,
        }]);
    }

    /**
     * Get the string representation of the query string.
     */
    public function value(): string
    {
        return (string) $this;
    }

    /**
     * Convert the query string into an array.
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Get the string representation of the query string.
     */
    public function __toString(): string
    {
        return Arr::query($this->toArray());
    }
}
