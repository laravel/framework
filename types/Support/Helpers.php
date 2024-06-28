<?php

use function PHPStan\Testing\assertType;

/** @var bool|float|int|string|null $value */
if (filled($value)) {
    assertType('bool|float|int|non-empty-string', $value);
} else {
    assertType('string|null', $value);
}

if (blank($value)) {
    assertType('string|null', $value);
} else {
    assertType('bool|float|int|non-empty-string', $value);
}

assertType('User', object_get(new User(), null));
assertType('User', object_get(new User(), ''));
assertType('mixed', object_get(new User(), 'name'));

assertType('int', once(fn () => 1));
assertType('null', once(function () { /** @phpstan-ignore function.void (testing void) */
}));

assertType('Illuminate\Support\Optional', optional());
assertType('null', optional(null, fn () => 1));
assertType('int', optional('foo', function ($value) {
    assertType('string', $value);

    return 1;
}));

assertType('int', retry(5, fn () => 1));

assertType('object', str());
assertType('Illuminate\Support\Stringable', str('foo'));

assertType('User', tap(new User(), function ($user) {
    assertType('User', $user);
}));
assertType('Illuminate\Support\HigherOrderTapProxy', tap(new User()));

assertType('bool', throw_if(true, Exception::class));

assertType('bool', throw_unless(true, Exception::class));

assertType('int', transform('filled', fn () => 1, true));
assertType('int', transform(['filled'], fn () => 1));
assertType('null', transform('', fn () => 1));
assertType('bool', transform('', fn () => 1, true));
assertType('bool', transform('', fn () => 1, fn () => true));

assertType('User', with(new User()));
assertType('bool', with(new User())->save());
assertType('int', with(new User(), function ($user) {
    assertType('User', $user);

    return 10;
}));
