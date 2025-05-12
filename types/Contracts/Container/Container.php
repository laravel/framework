<?php

use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

use function PHPStan\Testing\assertType;

$container = resolve(Container::class);

assertType('stdClass', $container->instance('foo', new stdClass));

assertType('mixed', $container->get('foo'));
assertType('Illuminate\Config\Repository', $container->get(Repository::class));

assertType('Closure(): mixed', $container->factory('foo'));
assertType('Closure(): Illuminate\Config\Repository', $container->factory(Repository::class));

assertType('mixed', $container->make('foo'));
assertType('Illuminate\Config\Repository', $container->make(Repository::class));

assertType('Illuminate\Http\Request', $container->instance('request', Request::capture()));
