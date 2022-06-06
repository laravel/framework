<?php

use function PHPStan\Testing\assertType;

assertType('Illuminate\Support\HigherOrderTapProxy|User', tap(new User()));

assertType('Illuminate\Support\HigherOrderTapProxy|User', tap(new User(), function ($user) {
    assertType('User', $user);
}));

assertType('User', with(new User()));
assertType('bool', with(new User())->save());
assertType('User', with(new User(), function (User $user) {
    return $user;
}));

assertType('int|User', with(new User(), function ($user) {
    assertType('int|User', $user);

    return 10;
}));
