<?php

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Enumerable;
use function PHPStan\Testing\assertType;

/** @var Enumerable<int, User> $enumerable */
$enumerable = collect([]);
class User extends Authenticatable
{
}

foreach ($enumerable as $int => $user) {
    assertType('int', $int);
    assertType('User', $user);
}

assertType('Illuminate\Support\Enumerable<int, string>', $enumerable::make(['string']));
assertType('Illuminate\Support\Enumerable<string, User>', $enumerable::make(['string' => new User]));

assertType('Illuminate\Support\Enumerable<int, User>', $enumerable::times(10, function ($int) {
    // assertType('int', $int);

    return new User;
}));

assertType('Illuminate\Support\Enumerable<int, User>', $enumerable->each(function ($user) {
    assertType('User', $user);
}));

assertType('Illuminate\Support\Enumerable<int, int>', $enumerable->range(1, 100));

assertType('Illuminate\Support\Enumerable<(int|string), int>', $enumerable->wrap(1));
assertType('Illuminate\Support\Enumerable<(int|string), string>', $enumerable->wrap('string'));
assertType('Illuminate\Support\Enumerable<(int|string), string>', $enumerable->wrap(['string']));
assertType('Illuminate\Support\Enumerable<(int|string), User>', $enumerable->wrap(['string' => new User]));

assertType('array<int, string>', $enumerable->unwrap(['string']));
assertType('array<int, User>', $enumerable->unwrap(
    $enumerable
));

assertType('Illuminate\Support\Enumerable<(int|string), mixed>', $enumerable::empty());

assertType('array<int, User>', $enumerable->all());

assertType('User|null', $enumerable->get(0));
assertType('string|User', $enumerable->get(0, 'string'));

assertType('User|null', $enumerable->first());
assertType('User|null', $enumerable->first(function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('string|User', $enumerable->first(function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
