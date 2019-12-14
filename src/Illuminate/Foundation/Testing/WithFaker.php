<?php

namespace Illuminate\Foundation\Testing;

use Faker\Factory;

trait WithFaker
{
    /**
     * The Faker instance.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Static Faker instance shared between test classes.
     *
     * @var \Faker\Generator[]
     */
    protected static $staticFakers = [];

    /**
     * Setup up the Faker instance.
     *
     * @return void
     */
    protected function setUpFaker()
    {
        $this->faker = $this->makeFaker();
    }

    /**
     * Get the default Faker instance for a given locale.
     *
     * @param  string|null  $locale
     * @return \Faker\Generator
     */
    protected function faker($locale = null)
    {
        return is_null($locale) ? $this->faker : $this->makeFaker($locale);
    }

    /**
     * Create a Faker instance for the given locale.
     *
     * @param  string|null  $locale
     * @return \Faker\Generator
     */
    protected function makeFaker($locale = null)
    {
        if (! isset(static::$staticFakers[$locale])) {
            static::$staticFakers[$locale] = Factory::create($locale ?? Factory::DEFAULT_LOCALE);
        }

        return static::$staticFakers[$locale];
    }
}
