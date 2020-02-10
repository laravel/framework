<?php

/**
 * This file is able to access the factory in a protected state as
 * it is being required from within the factory. Ideally this file should
 * be loaded from a safe instance (e.g. separate object or function) that
 * can only access public properties and methods of the factory.
 *
 * @see \Illuminate\Database\Eloquent\Factory::load()
 */

/** @var \Illuminate\Database\Eloquent\Factory $this */

use Faker\Generator as Faker;
use Illuminate\Tests\Integration\Database\FactoryViaThis;

$this->define(FactoryViaThis::class, function (Faker $faker) {
    return [
        'name' => 'this',
        'protected' => count($this->definitions), /* proof that factories are accessing protected state */
    ];
});
