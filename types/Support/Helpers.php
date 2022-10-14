<?php

use function PHPStan\Testing\assertType;

assertType('User', with(new User()));
assertType('bool', with(new User())->save());
assertType('User', with(new User(), static function (User $user) {
    return $user;
}));

assertType('int|User', with(new User(), static function ($user) {
    assertType('int|User', $user);

    return 10;
}));
