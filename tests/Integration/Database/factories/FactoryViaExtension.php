<?php

use Faker\Generator as Faker;
use Illuminate\Support\Facades\Factory;
use Illuminate\Tests\Integration\Database\FactoryViaExtension;
use Illuminate\Tests\Integration\Database\FactoryViaFacade;

Factory::define(FactoryViaExtension::class, function (Faker $faker) {
    return array_merge(
        factory(FactoryViaFacade::class)->raw(),
        ['extends' => FactoryViaFacade::class]
    );
});
