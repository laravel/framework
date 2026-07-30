<?php

use Illuminate\Support\Testing\Fakes\BatchRepositoryFake;

use function PHPStan\Testing\assertType;

/** @var BatchRepositoryFake $repository */
$repository = resolve(BatchRepositoryFake::class);

assertType("'foo'", $repository->transaction(fn () => 'foo'));
