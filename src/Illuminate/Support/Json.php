<?php

namespace Illuminate\Support;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Dumpable;
use Illuminate\Support\Traits\Tappable;
use JsonSerializable;
use stdClass;
use Stringable;

class Json implements Arrayable, Jsonable, JsonSerializable, Stringable, Responsable, ArrayAccess
{
    use Conditionable, Dumpable, Tappable;

    /**
     * The JSON data array.
     *
     * @var array
     */
    protected $items;

    /**
     * Create a new JSON transport.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $items
     */
    public function __construct($items = [])
    {
        $this->items = $items instanceof Arrayable ? $items->toArray() : $items;
    }

    /**
     * Adds a value to the underlying JSON data.
     *
     * @param  array-key  $key
     * @param  mixed  $value
     * @param  bool  $overwrite
     * @return void
     */
    public function set($key, $value, $overwrite = true): void
    {
        data_set($this->items, $key, $value, $overwrite);
    }

    /**
     * Fill in data where it's missing in the JSON data.
     *
     * @param  array-key  $key
     * @param  mixed  $value
     * @return void
     */
    public function fill($key, $value)
    {
        $this->set($key, $value, false);
    }

    /**
     * Retrieves a value from the underlying JSON data.
     *
     * @template TDefault
     *
     * @param  array-key  $key
     * @param  TDefault  $default
     * @return TDefault|mixed|null
     */
    public function get($key, $default = null)
    {
        return data_get($this->items, $key, $default);
    }

    /**
     * Determine if the given key in the JSON data exists and is not "null".
     *
     * @param  array-key  $key
     * @return bool
     */
    public function has($key)
    {
        return $this->get($key) !== null;
    }

    /**
     * Determine if the given key in the JSON data is missing or is "null".
     *
     * @param  array-key  $key
     * @return bool
     */
    public function missing($key)
    {
        return ! $this->has($key);
    }

    /**
     * Determine if the given key in the JSON data is set.
     *
     * @param  array-key  $key
     * @return bool
     */
    public function hasKey($key)
    {
        return $this->get($key, $placeholder = new stdClass) !== $placeholder;
    }

    /**
     * Determine if the given key in the JSON data is not set.
     *
     * @param  string  $key
     * @return bool
     */
    public function missingKey($key)
    {
        return ! $this->hasKey($key);
    }

    /**
     * Forgets a given key from the JSON data.
     *
     * @param  array-key  $key
     * @return void
     */
    public function forget($key)
    {
        Arr::forget($this->items, $key);
    }

    /**
     * Returns the underlying JSON items.
     *
     * @return array
     */
    public function items()
    {
        return $this->items;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object to its JSON representation, or throw an exception.
     *
     * @param  int  $options
     * @return string
     */
    public function toJsonOrFail($options = 0): string
    {
        return $this->toJson($options | JSON_THROW_ON_ERROR);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_map(fn ($value) => $value instanceof Arrayable ? $value->toArray() : $value, $this->items);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Returns a string representation of the object.
     *
     * @throws \JsonException
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function toResponse($request)
    {
        return new \Symfony\Component\HttpFoundation\JsonResponse($this);
    }

    /**
     * Dynamically retrieve a value from the JSON items.
     *
     * @param  string  $key
     * @return mixed|null
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Dynamically set a value in the JSON items.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Dynamically check if a key in the JSON items exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    /**
     * Dynamically forget a key from the JSON items.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset(string $key): void
    {
        $this->forget($key);
    }

    /**
     * Whether an offset exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Offset to retrieve.
     *
     * @param  mixed  $offset
     * @return mixed|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Offset to set.
     *
     * @param  array-key  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset.
     *
     * @param  array-key  $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->forget($offset);
    }

    /**
     * Create a new instance from a JSON string.
     *
     * @param  string  $json
     * @return static
     */
    public static function fromString(string $json): static
    {
        return new static(json_decode($json, true));
    }

    /**
     * Create a new instance.
     *
     * @param  array  $json
     * @return $this
     */
    public static function make($json = []): static
    {
        return new static($json);
    }
}
