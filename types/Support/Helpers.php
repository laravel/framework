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

assertType('mixed', with(new User(), function ($user) {
    return $user;
}));
assertType('User', with(new User(), function ($user): User {
    return $user;
}));
