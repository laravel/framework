<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\SqlServerConnection;
use LogicException;
use Mockery as m;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseSqlServerConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testTransactionBackoffThrowsExceptionForNonSqlsrvDrivers()
    {
        $pdo = m::mock(PDO::class);
        $pdo->shouldReceive('getAttribute')->with(PDO::ATTR_DRIVER_NAME)->andReturn('dblib');

        $connection = new SqlServerConnection($pdo, 'testdb');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Transaction attempt backoffs are only supported for "sqlsrv" driver connections.');

        $connection->transaction(function () {
            return 'test';
        }, attempts: 1, backoff: 100);
    }
}
