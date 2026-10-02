<?php

use Illuminate\Database\Connection;

use function PHPStan\Testing\assertType;

/** @var Connection $connection */
$connection = resolve(Connection::class);

assertType("'foo'", $connection->withoutPretending(fn () => 'foo'));
assertType("'foo'", $connection->withoutTablePrefix(fn () => 'foo'));
