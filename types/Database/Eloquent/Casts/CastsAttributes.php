<?php

use function PHPStan\Testing\assertType;

/** @var User $user */
/** @var \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Support\Stringable, string|\Stringable> $cast */
assertType('Illuminate\Support\Stringable|null', $cast->get($user, 'email', 'taylor@laravel.com', $user->getAttributes()));

$cast->set($user, 'email', 'taylor@laravel.com', $user->getAttributes()); // This works.
$cast->set($user, 'email', \Illuminate\Support\Str::of('taylor@laravel.com'), $user->getAttributes()); // This also works!
$cast->set($user, 'email', null, $user->getAttributes()); // Also valid.
