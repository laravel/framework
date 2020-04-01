<?php

namespace Illuminate\Database\Eloquent;

use ArrayAccess;
use Faker\Generator as Faker;
use Symfony\Component\Finder\Finder;

class Factory implements ArrayAccess
{
    /**
     * The model definitions in the container.
     *
     * @var array
     */
    protected $definitions = [];

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
    protected $afterMaking = [];

    /**
     * The registered after creating callbacks.
     *
     * @var array
     */
    protected $afterCreating = [];

    /**
     * The Faker instance for the builder.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Create a new factory instance.
     *
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
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

        return (new static($faker))->load($pathToFactories);
    }

    /**
     * Define a class with a given set of attributes.
     *
     * @param  string  $class
     * @param  callable  $attributes
     * @return $this
     */
    public function define($class, callable $attributes)
    {
        $this->definitions[$class] = $attributes;

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
        $this->states[$class][$state] = $attributes;

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
        $this->afterMaking[$class][$name][] = $callback;

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
        return $this->afterMaking($class, $callback, $state);
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
        $this->afterCreating[$class][$name][] = $callback;

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
        return $this->afterCreating($class, $callback, $state);
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
     * Get the raw attribute array for a given model.
     *
     * @param  string  $class
     * @param  array  $attributes
     * @return array
     */
    public function raw($class, array $attributes = [])
    {
        return array_merge(
            call_user_func($this->definitions[$class], $this->faker), $attributes
        );
    }

    /**
     * Create a builder for the given model.
     *
     * @param  string  $class
     * @return \Illuminate\Database\Eloquent\FactoryBuilder
     */
    public function of($class)
    {
        return new FactoryBuilder(
            $class, $this->definitions, $this->states,
            $this->afterMaking, $this->afterCreating, $this->faker
        );
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
        return isset($this->definitions[$offset]);
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
        unset($this->definitions[$offset]);
    }
}
