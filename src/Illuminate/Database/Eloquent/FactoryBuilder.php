<?php

namespace Illuminate\Database\Eloquent;

use Closure;
use Faker\Generator as Faker;
use InvalidArgumentException;

class FactoryBuilder
{
    /**
     * The model definitions in the container.
     *
     * @var array
     */
    protected $definitions;

    /**
     * The model being built.
     *
     * @var string
     */
    protected $class;

    /**
     * The name of the model being built.
     *
     * @var string
     */
    protected $name = 'default';

    /**
     * The number of models to build.
     *
     * @var int
     */
    protected $amount = 1;

    /**
     * The Faker instance for the builder.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Create an new builder instance.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $definitions
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct($class, $name, array $definitions, Faker $faker)
    {
        $this->name = $name;
        $this->class = $class;
        $this->faker = $faker;
        $this->definitions = $definitions;
    }

    /**
     * Set the amount of models you wish to create / make.
     *
     * @param  int  $amount
     * @return $this
     */
    public function times($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function create(array $attributes = [])
    {
        $results = $this->make($attributes);

        if ($this->amount === 1) {
            $results->save();
        } else {
            foreach ($results as $result) {
                $result->save();
            }
        }

        return $results;
    }

    /**
     * Create a collection of models.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function make(array $attributes = [])
    {
        if ($this->amount === 1) {
            return $this->makeInstance($attributes);
        }

        return new Collection(array_map(function () use ($attributes) {
            return $this->makeInstance($attributes);
        }, range(1, $this->amount)));
    }

    /**
     * Make an instance of the model with the given attributes.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \InvalidArgumentException
     */
    protected function makeInstance(array $attributes = [])
    {
        return Model::unguarded(function () use ($attributes) {
            if (! isset($this->definitions[$this->class][$this->name])) {
                throw new InvalidArgumentException("Unable to locate factory with name [{$this->name}] [{$this->class}].");
            }

            $definition = call_user_func(
                $this->definitions[$this->class][$this->name],
                $this->faker, $attributes
            );

            $evaluated = $this->callClosureAttributes(
                array_merge($definition, $attributes)
            );

            return new $this->class($evaluated);
        });
    }

    /**
     * Evaluate any Closure attributes on the attribute array.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function callClosureAttributes(array $attributes)
    {
        foreach ($attributes as &$attribute) {
            $attribute = $attribute instanceof Closure
                            ? $attribute($attributes) : $attribute;
        }

        return $attributes;
    }
}
