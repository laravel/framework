<?php

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;

use function PHPStan\Testing\assertType;

$app = resolve(Application::class);

assertType('stdClass', $app->instance('foo', new stdClass));

assertType('mixed', $app->get('foo'));
assertType('Illuminate\Config\Repository', $app->get(Repository::class));

assertType('Closure(): mixed', $app->factory('foo'));
assertType('Closure(): Illuminate\Config\Repository', $app->factory(Repository::class));

assertType('mixed', $app->make('foo'));
assertType('Illuminate\Config\Repository', $app->make(Repository::class));

assertType('mixed', $app->makeWith('foo'));
assertType('Illuminate\Config\Repository', $app->makeWith(Repository::class));

assertType('Illuminate\Config\Repository', $app->build(Repository::class));
assertType('Illuminate\Config\Repository', $app->build(function (Application $app, array $parameters) {
    return new Repository($parameters);
}));
assertType('stdClass', $app->build(function () {
    return new stdClass();
}));
