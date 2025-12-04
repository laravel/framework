<?php

use Illuminate\Events\Dispatcher;
use Illuminate\Log\Context\Repository;

use function PHPStan\Testing\assertType;

$repository = new Repository(new Dispatcher());

$value = $repository->scope(fn (): string => str_repeat('a', random_int(1, 4)));
assertType('string', $value);

$void = $repository->scope(function () { }); // @phpstan-ignore method.void
assertType('null', $void);
