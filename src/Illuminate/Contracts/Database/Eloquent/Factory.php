<?php

namespace Illuminate\Contracts\Database\Eloquent;

use ArrayAccess;
use Faker\Generator as Faker;

interface Factory extends ArrayAccess
{
    /**
     * Create a new factory container.
     *
     * @param  \Faker\Generator  $faker
     * @param  string|null  $pathToFactories
     * @return Factory
     */
    public static function construct(Faker $faker, $pathToFactories = null);

    /**
     * Define a class with a given short-name.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  callable  $attributes
     * @return $this
     */
    public function defineAs($class, $name, callable $attributes);

    /**
     * Define a class with a given set of attributes.
     *
     * @param  string  $class
     * @param  callable  $attributes
     * @param  string  $name
     * @return $this
     */
    public function define($class, callable $attributes, $name = 'default');

    /**
     * Define a state with a given set of attributes.
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable|array  $attributes
     * @return $this
     */
    public function state($class, $state, $attributes);

    /**
     * Define a callback to run after making a model.
     *
     * @param  string  $class
     * @param  callable  $callback
     * @param  string  $name
     * @return $this
     */
    public function afterMaking($class, callable $callback, $name = 'default');

    /**
     * Define a callback to run after making a model with given state.
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable  $callback
     * @return $this
     */
    public function afterMakingState($class, $state, callable $callback);

    /**
     * Define a callback to run after creating a model.
     *
     * @param  string  $class
     * @param  callable  $callback
     * @param  string $name
     * @return $this
     */
    public function afterCreating($class, callable $callback, $name = 'default');

    /**
     * Define a callback to run after creating a model with given state.
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable  $callback
     * @return $this
     */
    public function afterCreatingState($class, $state, callable $callback);

    /**
     * Create an instance of the given model and persist it to the database.
     *
     * @param  string  $class
     * @param  array  $attributes
     * @return mixed
     */
    public function create($class, array $attributes = []);

    /**
     * Create an instance of the given model and type and persist it to the database.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return mixed
     */
    public function createAs($class, $name, array $attributes = []);

    /**
     * Create an instance of the given model.
     *
     * @param  string  $class
     * @param  array  $attributes
     * @return mixed
     */
    public function make($class, array $attributes = []);

    /**
     * Create an instance of the given model and type.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return mixed
     */
    public function makeAs($class, $name, array $attributes = []);

    /**
     * Get the raw attribute array for a given named model.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $attributes
     * @return array
     */
    public function rawOf($class, $name, array $attributes = []);

    /**
     * Get the raw attribute array for a given model.
     *
     * @param  string  $class
     * @param  array  $attributes
     * @param  string  $name
     * @return array
     */
    public function raw($class, array $attributes = [], $name = 'default');

    /**
     * Create a builder for the given model.
     *
     * @param  string  $class
     * @param  string  $name
     * @return \Illuminate\Contracts\Database\Eloquent\FactoryBuilder
     */
    public function of($class, $name = 'default');

    /**
     * Load factories from path.
     *
     * @param  string  $path
     * @return $this
     */
    public function load($path);
}
