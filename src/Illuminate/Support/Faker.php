<?php

namespace Illuminate\Support;

use Exception;
use Faker\Factory;
use Faker\Generator;

class Faker extends Factory
{
    public Generator $faker;

    public function __construct()
    {
        $this->faker = self::create();
    }

    /**
     * Check the given method name.
     * Here we can choose to use our own method,
     * Or call a faker method.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return match ($name) {
            'image', 'alt' => $this->faker->$name(),
            default => $this->faker->$name(),
        };
    }

    /**
     * Returns a random picture from Lorem Picsum.
     *
     * @return string
     */
    public function image(): string
    {
        try {
            $rand = random_int(1, 100);
        } catch (Exception) {
            $rand = 1;
        }

        return "https://picsum.photos/200/300?random=$rand";
    }

    /**
     * Return a faker sentence to use for our random img.
     * @return string
     */
    public function alt(): string
    {
        return $this->faker->sentence();
    }
}
