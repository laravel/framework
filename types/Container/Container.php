<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use function PHPStan\Testing\assertType;

$container = new Container();
$user = new User();

assertType('User', $container->instance(User::class, $user));
