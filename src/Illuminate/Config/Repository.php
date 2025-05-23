<?php

namespace Illuminate\Config;

use ArrayAccess;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;

class Repository implements ArrayAccess, ConfigContract
{
    use Macroable;

    /**
     * All of the configuration items.
     *
     * @var array<string, mixed>
     */
    protected $items = [];

    /**
     * The configuration type casts.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    /**
     * Environment variable overrides.
     *
     * @var array<string, string>
     */
    protected $envOverrides = [];

    /**
     * Configuration validators.
     *
     * @var array<string, callable>
     */
    protected $validators = [];

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|null
     */
    protected $dispatcher;

    /**
     * Create a new configuration repository.
     *
     * @param  array  $items
     * @return void
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key): bool
    {
        return Arr::has($this->items, $key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        $value = $this->getWithEnvOverride($key, Arr::get($this->items, $key, $default));
        $value = $this->castValue($key, $value);
        $this->validateValue($key, $value);

        return $value;
    }

    /**
     * Get many configuration values.
     *
     * @param  array<string|int, mixed>  $keys
     * @return array<string, mixed>
     */
    public function getMany($keys): array
    {
        $results = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $results[$key] = $this->get($key, $default);
        }

        return $results;
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set($key, $value = null): void
    {
        $items = is_array($key) ? $key : [$key => $value];

        foreach ($items as $key => $value) {
            $this->validateValue($key, $value);
            Arr::set($this->items, $key, $value);
            $this->fireEvent('set', $key, $value);
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend($key, $value): void
    {
        $array = $this->get($key, []);
        array_unshift($array, $value);
        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value): void
    {
        $array = $this->get($key, []);
        $array[] = $value;
        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Get a configuration option.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set a configuration option.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->set($offset, null);
    }

    /**
     * Register a type cast for configuration items.
     *
     * @param  string  $key
     * @param  string  $type
     * @return void
     */
    public function cast(string $key, string $type): void
    {
        $this->casts[$key] = $type;
    }

    /**
     * Cast the configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castValue(string $key, $value): mixed
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }

        switch ($this->casts[$key]) {
            case 'string':
                return (string) $value;
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
                return (bool) $value;
            case 'array':
                return (array) $value;
            case 'object':
                return (object) $value;
            case 'json':
                return json_decode($value, true);
            case 'collection':
                return collect($value);
            default:
                return $value;
        }
    }

    /**
     * Set environment variable override for configuration.
     *
     * @param  string  $key
     * @param  string  $envVar
     * @return void
     */
    public function setEnvOverride(string $key, string $envVar): void
    {
        $this->envOverrides[$key] = $envVar;
    }

    /**
     * Get value with environment override.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function getWithEnvOverride(string $key, $default = null): mixed
    {
        if (isset($this->envOverrides[$key])) {
            $envValue = env($this->envOverrides[$key]);
            if ($envValue !== null) {
                return $envValue;
            }
        }

        return $default;
    }

    /**
     * Register a validator for a configuration key.
     *
     * @param  string    $key
     * @param  callable  $validator
     * @return void
     */
    public function validate(string $key, callable $validator): void
    {
        $this->validators[$key] = $validator;
    }

    /**
     * Validate a configuration value.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function validateValue(string $key, $value): void
    {
        if (isset($this->validators[$key])) {
            $isValid = call_user_func($this->validators[$key], $value);

            if (!$isValid) {
                throw new InvalidArgumentException("Configuration value for [{$key}] is invalid.");
            }
        }
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function setEventDispatcher(Dispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Fire a configuration event.
     *
     * @param  string  $event
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    protected function fireEvent(string $event, string $key, $value = null): void
    {
        if ($this->dispatcher) {
            $this->dispatcher->dispatch("config.{$event}: {$key}", [$key, $value, $this]);
        }
    }
}
