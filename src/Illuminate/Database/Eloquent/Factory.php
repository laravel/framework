<?php

namespace Illuminate\Database\Eloquent;

use ArrayAccess;
use Faker\Generator as Faker;
use Symfony\Component\Finder\Finder;
use Illuminate\Database\Eloquent\Factory\StateManager;

class Factory implements ArrayAccess
{
    /**
     * The Faker instance for the builder.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * The StateManager that holds all definitions.
     *
     * @var \Illuminate\Database\Eloquent\Factory\StateManager
     */
    protected $stateManager;

    /**
     * Create a new factory instance.
     *
     * @param  \Faker\Generator  $faker
     * @param  \Illuminate\Database\Eloquent\Factory\StateManager  $stateManager
     * @return void
     */
    public function __construct(Faker $faker, StateManager $stateManager)
    {
        $this->faker = $faker;
        $this->stateManager = $stateManager;
    }

    /**
     * Create a new factory container.
     *
     * @param  \Faker\Generator  $faker
     * @param  string|null  $pathToFactories
     * @return static
     */
    public static function construct(Faker $faker, $pathToFactories = null)
    {
        $pathToFactories = $pathToFactories ?: database_path('factories');

        return (new static($faker, new StateManager()))->load($pathToFactories);
    }

    /**
     * Define a class with a given short-name.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  callable  $attributes
     * @return $this
     */
    public function defineAs($class, $name, callable $attributes)
    {
        return $this->define($class, $attributes, $name);
    }

    /**
     * Define a class with a given set of attributes.
     *
     * @param  string  $class
     * @param  callable  $attributes
     * @param  string  $name
     * @return $this
     */
    public function define($class, callable $attributes, $name = 'default')
    {
        $this->stateManager->define($class, $name, $attributes);

        return $this;
    }

    /**
     * Define a preset with a callable.
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable  $callable
     * @return $this
     */
    public function preset($class, $state, callable $callable)
    {
        $this->stateManager->preset($class, $state, $callable);

        return $this;
    }

    /**
     * Define a state with a given set of attributes.
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable|array  $attributes
     * @return $this
     */
    public function state($class, $state, $attributes)
    {
        $this->stateManager->state($class, $state, $attributes);

        return $this;
    }

    /**
     * Define a callback to run after making a model.
     *
     * @param  string  $class
     * @param  callable  $callback
     * @param  string  $name
     * @return $this
     */
    public function afterMaking($class, callable $callback, $name = 'default')
    {
        $this->stateManager->afterMaking($class, $name, $callback);

        return $this;
    }

    /**
     * Define a callback to run after making a model with given state.
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable  $callback
     * @return $this
     */
    public function afterMakingState($class, $state, callable $callback)
    {
        $this->stateManager->afterMaking($class, $state, $callback);

        return $this;
    }

    /**
     * Define a callback to run after creating a model.
     *
     * @param  string  $class
     * @param  callable  $callback
     * @param  string  $name
     * @return $this
     */
    public function afterCreating($class, callable $callback, $name = 'default')
    {
        $this->stateManager->afterCreating($class, $name, $callback);

        return $this;
    }

    /**
     * Define a callback to run after creating a model with given state.
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable  $callback
     * @return $this
     */
    public function afterCreatingState($class, $state, callable $callback)
    {
        $this->stateManager->afterCreating($class, $state, $callback);

        return $this;
    }

    /**
     * Create an instance of the given model and persist it to the database.
     *
     * @param  string  $class
     * @param  array  $attributes
     * @return mixed
     */
    public function create($class, array $attributes = [])
    {
        return $this->of($class)->create($attributes);
    }

    /**
     * Create an instance of the given model and type and persist it to the database.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return mixed
     */
    public function createAs($class, $name, array $attributes = [])
    {
        return $this->of($class, $name)->create($attributes);
    }

    /**
     * Create an instance of the given model.
     *
     * @param  string  $class
     * @param  array  $attributes
     * @return mixed
     */
    public function make($class, array $attributes = [])
    {
        return $this->of($class)->make($attributes);
    }

    /**
     * Create an instance of the given model and type.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return mixed
     */
    public function makeAs($class, $name, array $attributes = [])
    {
        return $this->of($class, $name)->make($attributes);
    }

    /**
     * Get the raw attribute array for a given named model.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return array
     */
    public function rawOf($class, $name, array $attributes = [])
    {
        return $this->raw($class, $attributes, $name);
    }

    /**
     * Get the raw attribute array for a given model.
     *
     * @param  string  $class
     * @param  array  $attributes
     * @param  string  $name
     * @return array
     */
    public function raw($class, array $attributes = [], $name = 'default')
    {
        return array_merge(
            call_user_func($this->stateManager->getDefinition($class, $name), $this->faker), $attributes
        );
    }

    /**
     * Create a builder for the given model.
     *
     * @param  string  $class
     * @param  string  $name
     * @return \Illuminate\Database\Eloquent\FactoryBuilder
     */
    public function of($class, $name = 'default')
    {
        return tap(new FactoryBuilder($class, $this->stateManager, $this->faker))->definition($name);
    }

    /**
     * Load factories from path.
     *
     * @param  string  $path
     * @return $this
     */
    public function load($path)
    {
        $factory = $this;

        if (is_dir($path)) {
            foreach (Finder::create()->files()->name('*.php')->in($path) as $file) {
                require $file->getRealPath();
            }
        }

        return $factory;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->stateManager->definitionExists($offset);
    }

    /**
     * Get the value of the given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->make($offset);
    }

    /**
     * Set the given offset to the given value.
     *
     * @param  string  $offset
     * @param  callable  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->define($offset, $value);
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->stateManager->forgetDefinitions($offset);
    }
}
