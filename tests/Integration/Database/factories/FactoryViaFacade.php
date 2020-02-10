<?php

use Faker\Generator as Faker;
use Illuminate\Support\Facades\Factory;
use Illuminate\Tests\Integration\Database\FactoryViaFacade;

Factory::define(FactoryViaFacade::class, function (Faker $faker) {
    return [
        'name' => 'facade',
    ];
});
