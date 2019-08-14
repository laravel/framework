<?php

namespace Illuminate\Database\Eloquent\Factory;

class StateManager
{
    use NormalizesAttributes;

    /**
     * The model definitions in the container.
     *
     * @var array
     */
    protected $definitions = [];

    /**
     * The registered model presets.
     *
     * @var array
     */
    protected $presets = [];

    /**
     * The registered model states.
     *
     * @var array
     */
    protected $states = [];

    /**
     * The registered after making callbacks.
     *
     * @var array
     */
    public $afterMaking = [];

    /**
     * The registered after creating callbacks.
     *
     * @var array
     */
    public $afterCreating = [];

    /**
     * Define a class with a given short-name.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  callable|array  $builder
     * @return $this
     */
    public function define($class, $name, $builder)
    {
        $this->definitions[$class][$name] = $this->wrapCallable($builder);

        return $this;
    }

    /**
     * Check if a definition exists.
     *
     * @param  string  $class
     * @param  string|null  $name
     * @return bool
     */
    public function definitionExists($class, $name = null)
    {
        return is_null($name)
            ? isset($this->definitions[$class])
            : isset($this->definitions[$class][$name]);
    }

    /**
     * Delete an existing definition.
     *
     * @param  string  $class
     * @return $this
     */
    public function forgetDefinitions($class)
    {
        unset($this->definitions[$class]);

        return $this;
    }

    /**
     * Get a definition.
     *
     * @param  string  $class
     * @param  string  $name
     * @return \Closure
     */
    public function getDefinition($class, $name)
    {
        return data_get($this->definitions, "{$class}.{$name}") ?: $this->wrapCallable([]);
    }

    /**
     * Define a preset that may later be used to configure a factory.
     *
     * @param  string  $class
     * @param  string  $preset
     * @param  callable|array  $builder
     * @return $this
     */
    public function preset($class, $preset, $builder)
    {
        $this->presets[$class][$preset] = $this->wrapCallable($builder);

        return $this;
    }

    /**
     * Check if presets exists.
     *
     * @param  string  $class
     * @param  string|array  $presets
     * @return bool
     */
    public function presetsExists($class, $presets)
    {
        return collect($presets)->reject(function ($preset) use ($class) {
            return data_get($this->presets, "{$class}.{$preset}") !== null;
        })->isEmpty();
    }

    /**
     * Get a preset.
     *
     * @param  string  $class
     * @param  string  $preset
     * @return \Closure
     */
    public function getPreset($class, $preset)
    {
        return data_get($this->presets, "{$class}.{$preset}");
    }

    /**
     * Define a state with a given set of attributes.
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable|array  $builder
     * @return $this
     */
    public function state($class, $state, $builder)
    {
        $this->states[$class][$state] = $this->wrapCallable($builder);

        return $this;
    }

    /**
     * Check if states exists.
     *
     * @param  string  $class
     * @param  string|array  $states
     * @return bool
     */
    public function statesExists($class, $states)
    {
        return collect($states)->reject(function ($states) use ($class) {
            return data_get($this->states, "{$class}.{$states}") !== null;
        })->isEmpty();
    }

    /**
     * Get a state.
     *
     * @param  string  $class
     * @param  string  $state
     * @return \Closure
     */
    public function getState($class, $state)
    {
        return data_get($this->states, "{$class}.{$state}");
    }

    /**
     * Define a callback to run after making a model.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  callable  $callback
     * @return $this
     */
    public function afterMaking($class, $name, callable $callback)
    {
        $this->afterMaking[$class][$name][] = $callback;

        return $this;
    }

    /**
     * Define a callback to run after creating a model.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  callable  $callback
     * @return $this
     */
    public function afterCreating($class, $name, callable $callback)
    {
        $this->afterCreating[$class][$name][] = $callback;

        return $this;
    }

    /**
     * Determine if a callback exists on a given model.
     *
     * @param  string  $class
     * @param  string  $name
     * @return bool
     */
    public function afterCallbackExists($class, $name)
    {
        return isset($this->afterMaking[$class][$name]) ||
            isset($this->afterCreating[$class][$name]);
    }
}
