<?php

namespace Illuminate\Tests\Database\Exceptions;

use Exception;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\TestCase;

class QueryExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfPDOException()
    {
        $exception = new QueryException('mysql', 'select * from users', [], new Exception('Syntax error'));

        $this->assertInstanceOf(PDOException::class, $exception);
    }

    public function testExceptionHoldsConnectionSqlAndBindings()
    {
        $previous = new Exception('Syntax error');

        $exception = new QueryException(
            'mysql', 'select * from users where id = ?', [1], $previous
        );

        $this->assertSame('mysql', $exception->getConnectionName());
        $this->assertSame('select * from users where id = ?', $exception->getSql());
        $this->assertSame([1], $exception->getBindings());
        $this->assertSame(
            'Syntax error (Connection: mysql, SQL: select * from users where id = 1)',
            $exception->getMessage()
        );
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionInheritsCodeFromPreviousException()
    {
        $previous = new Exception('Syntax error', 1234);

        $exception = new QueryException('mysql', 'select 1', [], $previous);

        $this->assertSame(1234, $exception->getCode());
    }

    public function testExceptionInheritsErrorInfoFromPreviousPDOException()
    {
        $previous = new PDOException('Syntax error');
        $previous->errorInfo = ['42S02', 1146, "Table 'db.users' doesn't exist"];

        $exception = new QueryException('mysql', 'select 1', [], $previous);

        $this->assertSame(['42S02', 1146, "Table 'db.users' doesn't exist"], $exception->errorInfo);
    }

    public function testExceptionFormatsMessageWithHostAndPort()
    {
        $exception = new QueryException('mysql', 'select 1', [], new Exception('Syntax error'), [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'forge',
        ]);

        $this->assertSame(
            'Syntax error (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: forge, SQL: select 1)',
            $exception->getMessage()
        );
        $this->assertSame([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'forge',
        ], $exception->getConnectionDetails());
    }

    public function testExceptionFormatsMessageWithUnixSocket()
    {
        $exception = new QueryException('mysql', 'select 1', [], new Exception('Syntax error'), [
            'driver' => 'mysql',
            'unix_socket' => '/var/run/mysqld/mysqld.sock',
            'database' => 'forge',
        ]);

        $this->assertSame(
            'Syntax error (Connection: mysql, Socket: /var/run/mysqld/mysqld.sock, Database: forge, SQL: select 1)',
            $exception->getMessage()
        );
    }

    public function testExceptionFormatsMessageWithoutHostForSqliteDriver()
    {
        $exception = new QueryException('sqlite', 'select 1', [], new Exception('Syntax error'), [
            'driver' => 'sqlite',
            'database' => '/database.sqlite',
        ]);

        $this->assertSame(
            'Syntax error (Connection: sqlite, Database: /database.sqlite, SQL: select 1)',
            $exception->getMessage()
        );
    }

    public function testExceptionHoldsReadWriteType()
    {
        $exception = new QueryException(
            'mysql', 'select 1', [], new Exception('Syntax error'), [], 'write'
        );

        $this->assertSame('write', $exception->readWriteType);
    }
}
