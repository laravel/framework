<?php

use Illuminate\Bus\DatabaseBatchRepository;

use function PHPStan\Testing\assertType;

/** @var DatabaseBatchRepository $repository */
$repository = resolve(DatabaseBatchRepository::class);

assertType("'foo'", $repository->transaction(fn () => 'foo'));
