<?php

use function PHPStan\Testing\assertType;

assertType("'foo'", value('foo', 42));
assertType('42', value(fn () => 42));
assertType('42', value(function ($foo) {
    assertType('true', $foo);

    return 42;
}, true));
