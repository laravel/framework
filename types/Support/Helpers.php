<?php

use function PHPStan\Testing\assertType;

assertType('User', with(new User()));
assertType('bool', with(new User())->save());

assertType('User', with(new User(), function (User $user) {
    return $user;
}));
assertType('User', with(new User(), function (User $user): User {
    return $user;
}));

assertType('User', with(new User(), function ($user) {
    /** @var User $user */
    return $user;
}));
assertType('User', with(new User(), function ($user): User {
    /** @var User $user */
    return $user;
}));

assertType('int', with(new User(), function ($user) {
    assertType('User', $user);

    return 10;
}));
assertType('int', with(new User(), function ($user): int {
    assertType('User', $user);

    return 10;
}));

assertType('User', with(new User(), function ($user) {
    return $user;
}));
assertType('User', with(new User(), function ($user): User {
    return $user;
}));

// falls back to default if provided
assertType('int|null', transform(optional(), fn () => 1));
// default as callable
assertType('int|string', transform(optional(), fn () => 1, fn () => 'string'));

// non empty values
assertType('int', transform('filled', fn () => 1));
assertType('int', transform(['filled'], fn () => 1));
assertType('int', transform(new User(), fn () => 1));

// "empty" values
assertType('null', transform(null, fn () => 1));
assertType('null', transform('', fn () => 1));
assertType('null', transform([], fn () => 1));

assertType('int|null', rescue(fn () => 123));
assertType('int', rescue(fn () => 123, 345));
assertType('int', rescue(fn () => 123, fn () => 345));
