<?php

use Illuminate\Database\SqlServerConnection;

use function PHPStan\Testing\assertType;

/** @var SqlServerConnection $connection */
$connection = resolve(SqlServerConnection::class);

assertType("'foo'", $connection->transaction(fn () => 'foo'));
