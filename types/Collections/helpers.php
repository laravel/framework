<?php

use function PHPStan\Testing\assertType;

assertType('string', value('foo', 42));
assertType('int', value(fn () => 42));
assertType('int', value(function ($foo) {
    assertType('bool', $foo);

    return 42;
}, true));
