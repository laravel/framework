<?php

use Illuminate\Support\Arr;

use function PHPStan\Testing\assertType;

$array = [new User];
/** @var iterable<int, User> $iterable */
$iterable = [];
/** @var Traversable<int, User> $traversable */
$traversable = [];

assertType('User|null', Arr::first($array));
assertType('User|null', Arr::first($array, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('string|User', Arr::first($array, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType('string|User', Arr::first($array, null, function () {
    return 'string';
}));

assertType('User|null', Arr::last($array));
assertType('User|null', Arr::last($array, function ($user) {
    assertType('User', $user);

    return true;
}));
assertType('string|User', Arr::last($array, function ($user) {
    assertType('User', $user);

    return false;
}, 'string'));
assertType('string|User', Arr::last($array, null, function () {
    return 'string';
}));
