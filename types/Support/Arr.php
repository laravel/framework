<?php

use Illuminate\Support\Arr;

use function PHPStan\Testing\assertType;

/** @var array<int, User> $array */
$array = [new User];
/** @var iterable<int, User> $iterable */
$iterable = [];
/** @var Traversable<int, User> $traversable */
$traversable = new ArrayIterator([new User]);
/** @var array<string, User> $associativeArray */
$associativeArray = ['John' => new User];

assertType('User|null', Arr::first($array));
assertType('User|null', Arr::first($array, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::first($array, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::first($array, null, function () {
    return 'string';
}));

assertType('User|null', Arr::first($iterable));
assertType('User|null', Arr::first($iterable, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::first($iterable, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::first($iterable, null, function () {
    return 'string';
}));

assertType('User|null', Arr::first($traversable));
assertType('User|null', Arr::first($traversable, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::first($traversable, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::first($traversable, null, function () {
    return 'string';
}));

assertType('User|null', Arr::last($array));
assertType('User|null', Arr::last($array, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::last($array, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::last($array, null, function () {
    return 'string';
}));

assertType('User|null', Arr::last($iterable));
assertType('User|null', Arr::last($iterable, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::last($iterable, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::last($iterable, null, function () {
    return 'string';
}));

assertType('User|null', Arr::last($traversable));
assertType('User|null', Arr::last($traversable, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType("'string'|User", Arr::last($traversable, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType("'string'|User", Arr::last($traversable, null, function () {
    return 'string';
}));

assertType('array<int<0, 1>, array<string, User>>', Arr::partition($associativeArray, fn ($user) => true));
assertType('array<int<0, 1>, list<User>>', Arr::partition($associativeArray, fn ($user) => true, false));
