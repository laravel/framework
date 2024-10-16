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

function testThrowIf(float|int $foo, ?DateTime $bar = null): void
{
    assertType('never', throw_if(true, Exception::class));
    assertType('bool', throw_if(false, Exception::class));
    assertType('false', throw_if(empty($foo)));
    throw_if(is_float($foo));
    assertType('int', $foo);
    throw_if($foo == false);
    assertType('int<min, -1>|int<1, max>', $foo);

    // Truthy/falsey argument
    throw_if($bar);
    assertType('null', $bar);
    assertType('null', throw_if(null, Exception::class));
    assertType('string', throw_if('', Exception::class));
    assertType('never', throw_if('foo', Exception::class));
}

function testThrowUnless(float|int $foo, ?DateTime $bar = null): void
{
    assertType('bool', throw_unless(true, Exception::class));
    assertType('never', throw_unless(false, Exception::class));
    assertType('true', throw_unless(empty($foo)));
    throw_unless(is_int($foo));
    assertType('int', $foo);
    throw_unless($foo == false);
    assertType('0', $foo);
    throw_unless($bar instanceof DateTime);
    assertType('DateTime', $bar);

    // Truthy/falsey argument
    assertType('never', throw_unless(null, Exception::class));
    assertType('never', throw_unless('', Exception::class));
    assertType('string', throw_unless('foo', Exception::class));
}

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
