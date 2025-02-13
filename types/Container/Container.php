<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;

use function PHPStan\Testing\assertType;

$container = resolve(Container::class);

assertType('stdClass', $container->instance('foo', new stdClass));

assertType('mixed', $container->get('foo'));
assertType('Illuminate\Config\Repository', $container->get(Repository::class));

assertType('Closure(): mixed', $container->factory('foo'));
assertType('Closure(): Illuminate\Config\Repository', $container->factory(Repository::class));

assertType('mixed', $container->make('foo'));
assertType('Illuminate\Config\Repository', $container->make(Repository::class));

assertType('mixed', $container->makeWith('foo'));
assertType('Illuminate\Config\Repository', $container->makeWith(Repository::class));

assertType('Illuminate\Config\Repository', $container->build(Repository::class));
assertType('Illuminate\Config\Repository', $container->build(function (Container $container, array $parameters) {
    return new Repository($parameters);
}));
assertType('stdClass', $container->build(function () {
    return new stdClass();
}));
