<?php

namespace Illuminate\Database\Eloquent;

use Exception;
use Faker\Generator as Faker;

abstract class FactoryClass
{
    /**
     * The Faker instance.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * The Eloquent model associated to this factory class.
     *
     * @var string
     */
    protected $model;

    /**
     * Create a new factory class instance.
     *
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct()
    {
        $this->faker = app(Faker::class);
    }

    /**
     * Return the basic data for this model factory.
     *
     * @return array
     */
    abstract public function data();

    /**
     * Return the Eloquent model associated to this factory class.
     *
     * @return string
     *
     * @throws Exception
     */
    public function model()
    {
        if (! $this->model) {
            throw new Exception(
                'Please define the $model property in the '.get_class($this).' class'
            );
        }

        return $this->model;
    }

    /**
     * Proxy to get the Faker data.
     *
     * @param $var
     * @return mixed
     */
    public function __get($var)
    {
        return $this->faker->$var;
    }

    /**
     * Proxy to call the Faker methods.
     *
     * @param  string  $method
     * @param  array  $attributes
     * @return mixed
     */
    public function __call($method, array $attributes = [])
    {
        return $this->faker->$method(...$attributes);
    }

    /**
     * Create and return a new factory class builder.
     *
     * @param  string  $method
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\FactoryClassBuilder
     */
    public static function __callStatic($method, array $attributes = [])
    {
        return (new FactoryClassBuilder(new static))->$method(...$attributes);
    }
}
