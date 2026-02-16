<?php

use function PHPStan\Testing\assertType;

assertType("'foo'", value('foo', 42));
assertType('42', value(fn () => 42));
assertType('42', value(function ($foo) {
    assertType('true', $foo);

    return 42;
}, true));

assertType("'foo'", when(true, 'foo'));
assertType("'foo'", when(true, 'foo', 42));
assertType('null', when(false, 'foo'));
assertType('42', when(false, 'foo', 42));
assertType("'foo'", when(true, fn () => 'foo'));
assertType("'foo'", when(fn() => 'foo', fn ($value) => $value));
assertType("'foo'", when(true, fn () => 'foo', fn () => 42));
assertType('null', when(false, fn () => 'foo'));
assertType('42', when(false, fn () => 'foo', fn () => 42));
assertType("'foo'", when(1, 'foo', 42));
assertType("'foo'", when(42, 'foo'));
assertType('null', when(0, 'foo'));
assertType('null', when(-42, 'foo'));
assertType('null', when(null, 'foo'));
assertType('42', when(['foo'], 42));
assertType('null', when([], 42));
assertType('42|null', when(random_int(0, 1), 42));
assertType('42|1337', when(random_int(0, 1), 42, 1337));
assertType("array{'bar'}|array{'foo'}", when(random_int(0, 1), ['foo'], ['bar']));
assertType('42|null', when(fn () => random_int(0, 1), 42));
assertType('42|1337', when(fn () => random_int(0, 1), 42, 1337));
