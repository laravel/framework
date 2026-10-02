<?php

use Illuminate\Database\ConnectionInterface;

use function PHPStan\Testing\assertType;

/** @var ConnectionInterface $connection */
$connection = resolve(ConnectionInterface::class);

assertType("'foo'", $connection->transaction(fn () => 'foo'));
