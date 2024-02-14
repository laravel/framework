<?php

namespace Illuminate\Log\Context;

use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\Context\Events\Dehydrating;
use Illuminate\Log\Context\Events\Hydrated;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;

class Repository
{
    use Macroable;

    /**
     * The event dispatcher.
     *
     * @var \Illuminate\Events\Dispatcher
     */
    protected $events;

    /**
     * The contextual data.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * The hidden contextual data.
     *
     * @var array<string, mixed>
     */
    protected $hidden = [];

    /**
     * Create a new Context instance.
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Set the given key's value.
     *
     * @param  string|array<string, mixed>  $key
     * @param  mixed  $value
     * @return $this
     */
    public function add($key, $value = null)
    {
        $values = is_array($key) ? $key : [$key => $value];

        foreach ($values as $key => $value) {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Set the given key's value as hidden.
     *
     * @param  string|array<string, mixed>  $key
     * @param  mixed  $value
     * @return $this
     */
    public function addHidden($key, $value = null)
    {
        $values = is_array($key) ? $key : [$key => $value];

        foreach ($values as $key => $value) {
            $this->hidden[$key] = $value;
        }

        return $this;
    }

    /**
     * Forget the given key's context.
     *
     * @param  string|array<int, string>  $key
     * @return $this
     */
    public function forget($key)
    {
        foreach ((array) $key as $k) {
            unset($this->data[$k]);
        }

        return $this;
    }

    /**
     * Forget the given key's hidden context.
     *
     * @param  string|array<int, string>  $key
     * @return $this
     */
    public function forgetHidden($key)
    {
        foreach ((array) $key as $k) {
            unset($this->hidden[$k]);
        }

        return $this;
    }

    /**
     * Set the given key's value if it does not yet exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function addIf($key, $value)
    {
        if (! $this->has($key)) {
            $this->add($key, $value);
        }

        return $this;
    }

    /**
     * Set the given key's value as hidden if it does not yet exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function addHiddenIf($key, $value)
    {
        if (! $this->hasHidden($key)) {
            $this->addHidden($key, $value);
        }

        return $this;
    }

    /**
     * Retrieve the given key's value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Retrieve the given key's hidden value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getHidden($key)
    {
        return $this->hidden[$key] ?? null;
    }

    /**
     * Retrieve only the values of the given keys.
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function only($keys)
    {
        return array_reduce($keys, function ($carry, $key) {
            if (! $this->has($key)) {
                return $carry;
            }

            return [
                ...$carry,
                ...[$key => $this->get($key)],
            ];
        }, []);
    }

    /**
     * Retrieve only the hidden values of the given keys.
     *
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function onlyHidden($keys)
    {
        return array_reduce($keys, function ($carry, $key) {
            if (! $this->hasHidden($key)) {
                return $carry;
            }

            return [
                ...$carry,
                ...[$key => $this->getHidden($key)],
            ];
        }, []);
    }

    /**
     * Push the given values onto the key's stack.
     *
     * @param  string  $key
     * @param  mixed  ...$values
     * @return $this
     */
    public function push($key, ...$values)
    {
        if (! $this->isStackable($key)) {
            throw new RuntimeException("Unable to push value onto context stack for key [{$key}].");
        }

        $this->data[$key] = [
            ...$this->data[$key] ?? [],
            ...$values,
        ];

        return $this;
    }

    /**
     * Push the given values onto the key's hidden stack.
     *
     * @param  string  $key
     * @param  mixed  ...$values
     * @return $this
     */
    public function pushHidden($key, ...$values)
    {
        if (! $this->isHiddenStackable($key)) {
            throw new RuntimeException("Unable to push value onto hidden context stack for key [{$key}].");
        }

        $this->hidden[$key] = [
            ...$this->hidden[$key] ?? [],
            ...$values,
        ];

        return $this;
    }

    /**
     * Determine if the given key exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Determine if the given key exists as hidden.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasHidden($key)
    {
        return array_key_exists($key, $this->hidden);
    }

    /**
     * Execute the given callback when context is about to be dehydrated.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function dehydrating($callback)
    {
        $this->events->listen(fn (Dehydrating $event) => $callback($this));

        return $this;
    }

    /**
     * Execute the given callback when context has been hydrated.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function hydrated($callback)
    {
        $this->events->listen(fn (Hydrated $event) => $callback($this));

        return $this;
    }

    /**
     * Retrieve all the values.
     *
     * @return array<string, mixed>
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Retrieve all the hidden values.
     *
     * @return array<string, mixed>
     */
    public function allHidden()
    {
        return $this->hidden;
    }

    /**
     * Determine if a given key can used as a stack.
     */
    public function isStackable($key)
    {
        if (! $this->has($key)) {
            return true;
        }

        if (is_array($this->data[$key]) && array_is_list($this->data[$key])) {
            return true;
        }

        return false;
    }

    /**
     * Determine if a given key can used as a hidden stack.
     */
    public function isHiddenStackable($key)
    {
        if (! $this->hasHidden($key)) {
            return true;
        }

        if (is_array($this->hidden[$key]) && array_is_list($this->hidden[$key])) {
            return true;
        }

        return false;
    }

    /**
     * Flush all state.
     *
     * @return $this
     */
    public function flush()
    {
        $this->data = [];

        $this->hidden = [];

        return $this;
    }
}
