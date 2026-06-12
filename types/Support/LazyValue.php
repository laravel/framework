<?php

use Illuminate\Support\LazyValue;

use function PHPStan\Testing\assertType;

$myCallback = static fn() => 123;
$lazyValue = new LazyValue($myCallback);
assertType('int', $lazyValue->value());
assertType('int', $lazyValue->__invoke());
assertType('int', $lazyValue());
