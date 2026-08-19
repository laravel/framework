<?php

use Illuminate\Bus\BatchRepository;

use function PHPStan\Testing\assertType;

/** @var BatchRepository $repository */
$repository = resolve(BatchRepository::class);

assertType("'foo'", $repository->transaction(fn () => 'foo'));
