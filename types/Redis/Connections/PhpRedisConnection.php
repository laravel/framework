<?php

use Illuminate\Redis\Connections\PhpRedisConnection;

use function PHPStan\Testing\assertType;

/** @var PhpRedisConnection $connection */
$connection = resolve(PhpRedisConnection::class);

assertType("'foo'", $connection->withoutSerializationOrCompression(fn () => 'foo'));
