<?php

use Illuminate\Events\Dispatcher;
use Illuminate\Log\Context\Repository;

use function PHPStan\Testing\assertType;

$repository = new Repository(new Dispatcher());

$value = $repository->scope(fn (): string => 'Macca');
assertType('string', $value);

$void = $repository->scope(function () { });
assertType('null', $void);
