<?php

namespace Illuminate\Foundation\Testing;

use Faker\Factory;
use Faker\Generator;

trait WithFaker
{
    /**
     * Faker generator instance.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Setup up faker generator instance.
     *
     * @return void
     */
    protected function setUpFaker()
    {
        $this->faker = $this->makeFaker();
    }

    /**
     * Get a default faker generator instance or get a new one for given locale.
     *
     * @param  string  $locale
     * @return \Faker\Generator
     */
    protected function faker(string $locale = null)
    {
        if (is_null($locale)) {
            return $this->faker;
        }

        return $this->makeFaker($locale);
    }

    /**
     * Set a new faker generator instance for given locale.
     *
     * @param  string  $locale
     * @return void
     */
    protected function fakerSetLocale(string $locale)
    {
        $this->faker = $this->makeFaker($locale);
    }

    /**
     * Make a faker generator instance for given or default locale.
     *
     * @param  string  $locale
     * @return \Faker\Generator
     */
    protected function makeFaker(string $locale = null)
    {
        if (is_null($locale)) {
            $locale = Factory::DEFAULT_LOCALE;
        }

        return Factory::create($locale);
    }
}
