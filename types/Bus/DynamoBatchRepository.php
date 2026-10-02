<?php

use Illuminate\Bus\DynamoBatchRepository;

use function PHPStan\Testing\assertType;

/** @var DynamoBatchRepository $repository */
$repository = resolve(DynamoBatchRepository::class);

assertType("'foo'", $repository->transaction(fn () => 'foo'));
