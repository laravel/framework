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
        $this->definitions[$class][$name] = $attributes;

        return $this;
    }

    /**
     * Define a state with a given set of attributes.
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable  $attributes
     * @return $this
     */
    public function state($class, $state, callable $attributes)
    {
        $this->states[$class][$state] = $attributes;

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
            call_user_func($this->definitions[$class][$name], $this->faker), $attributes
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
        return new FactoryBuilder($class, $name, $this->definitions, $this->states, $this->faker);
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
        return $this->define($offset, $value);
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
