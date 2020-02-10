<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use Illuminate\Tests\Integration\Database\FactoryViaVariable;

$factory->define(FactoryViaVariable::class, function (Faker $faker) {
    return [
        'name' => 'variable',
    ];
});
