<?php

use Illuminate\Events\Dispatcher;
use Illuminate\Log\Context\Repository;

use function PHPStan\Testing\assertType;

$repository = new Repository(new Dispatcher());

$value = $repository->scope(fn (): int => random_int(-100, 100));
assertType('int<-100, 100>', $value);

$void = $repository->scope(function () { // @phpstan-ignore method.void
});
assertType('null', $void);
