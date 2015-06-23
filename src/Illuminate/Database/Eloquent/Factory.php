<?php

namespace Illuminate\Database\Eloquent;

use ArrayAccess;
use Faker\Factory as Faker;
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
     * Default locale used on faker.
     *
     * @var string
     */
    protected $fakerLocale ;

    /**
     * The Faker instance for the factory.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Create a new factory container.
     *
     * @param  string|null  $pathToFactories
     * @param  string|null  $fakerLocale
     * @return static
     */
    public static function construct($pathToFactories = null, $fakerLocale = null)
    {
        $pathToFactories = $pathToFactories ?: database_path('factories');

        $factory = new static;

        $factory->setFakerLocale($fakerLocale);

        if (is_dir($pathToFactories)) {
            foreach (Finder::create()->files()->in($pathToFactories) as $file) {
                require $file->getRealPath();
            }
        }

        return $factory;
    }

    /**
     * Define a class with a given short-name.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  callable  $attributes
     * @return void
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
     * @return void
     */
    public function define($class, callable $attributes, $name = 'default')
    {
        $this->definitions[$class][$name] = $attributes;
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
        $raw = call_user_func($this->definitions[$class][$name], $this->getFaker());

        return array_merge($raw, $attributes);
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
        return new FactoryBuilder($class, $name, $this->definitions, $this->getFaker());
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

    /**
     * Get a new instance of faker locale or the latest created.
     *
     * @param  string|null  $locale
     * @return \Faker\Generator
     */
    public function getFaker($locale = null)
    {
        if (!$this->faker) {
            $locale = $locale ?: $this->getFakerLocale();

            $this->faker = Faker::create($locale);
        }

        return $this->faker;
    }

    /**
     * Set the locale to faker.
     *
     * @param  string|null  $locale
     * @return void
     */
    public function setFakerLocale($locale = null)
    {
        /*
         * Reset faker instance
         */
        $this->faker = null;

        $this->fakerLocale = $locale;
    }

    /**
     * Get faker locale.
     *
     * @return string
     */
    public function getFakerLocale()
    {
        return $this->fakerLocale ?: Faker::DEFAULT_LOCALE;
    }
}
