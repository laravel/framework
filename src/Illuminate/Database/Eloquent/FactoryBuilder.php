<?php

namespace Illuminate\Database\Eloquent;

use Faker\Generator as Faker;
use InvalidArgumentException;
use Illuminate\Contracts\Container\Container;

class FactoryBuilder
{
    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

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
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  \Faker\Generator  $faker
     * @param  array  $definitions
     * @param  string  $class
     * @param  string  $name
     * @return void
     */
    public function __construct(Container $container, Faker $faker, array $definitions, $class, $name)
    {
        $this->container = $container;
        $this->faker = $faker;
        $this->definitions = $definitions;
        $this->class = $class;
        $this->name = $name;
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
        } else {
            $results = [];

            for ($i = 0; $i < $this->amount; $i++) {
                $results[] = $this->makeInstance($attributes);
            }

            return new Collection($results);
        }
    }

    /**
     * Make an instance of the model with the given attributes.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function makeInstance(array $attributes = [])
    {
        return Model::unguarded(function () use ($attributes) {
            if (! isset($this->definitions[$this->class][$this->name])) {
                throw new InvalidArgumentException("Unable to locate factory with name [{$this->name}].");
            }

            $definition = $this->container->call($this->definitions[$this->class][$this->name], [$this->faker, $attributes]);

            return new $this->class(array_merge($definition, $attributes));
        });
    }
}
