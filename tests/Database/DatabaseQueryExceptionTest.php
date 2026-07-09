<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Mockery as m;
use PDOException;
use PHPUnit\Framework\TestCase;

class DatabaseQueryExceptionTest extends TestCase
{
    public function testIfItEmbedsBindingsIntoSql()
    {
        $connection = $this->getConnection();

        $sql = 'SELECT * FROM huehue WHERE a = ? and hue = ?';
        $bindings = [1, 'br'];

        $expectedSql = "SELECT * FROM huehue WHERE a = 1 and hue = 'br'";

        $pdoException = new PDOException('Mock SQL error');
        $exception = new QueryException($connection->getName(), $sql, $bindings, $pdoException);

        DB::shouldReceive('connection')->andReturn($connection);
        $result = $exception->getRawSql();

        $this->assertSame($expectedSql, $result);
    }

    public function testIfItReturnsSameSqlWhenThereAreNoBindings()
    {
        $connection = $this->getConnection();

        $sql = "SELECT * FROM huehue WHERE a = 1 and hue = 'br'";
        $bindings = [];

        $expectedSql = $sql;

        $pdoException = new PDOException('Mock SQL error');
        $exception = new QueryException($connection->getName(), $sql, $bindings, $pdoException);

        DB::shouldReceive('connection')->andReturn($connection);
        $result = $exception->getRawSql();

        $this->assertSame($expectedSql, $result);
    }

    public function testMessageIncludesConnectionInfo()
    {
        $pdoException = new PDOException('SQLSTATE[HY000] [2002] No such file or directory');
        $exception = new QueryException('mysql::read', 'SELECT * FROM users', [], $pdoException, [
            'driver' => 'mysql',
            'name' => 'mysql::read',
            'host' => '192.168.1.10',
            'port' => '3306',
            'database' => 'laravel_db',
            'unix_socket' => null,
        ]);

        $this->assertStringContainsString('Host: 192.168.1.10', $exception->getMessage());
        $this->assertStringContainsString('Port: 3306', $exception->getMessage());
        $this->assertStringContainsString('Database: laravel_db', $exception->getMessage());
        $this->assertStringContainsString('Connection: mysql::read', $exception->getMessage());
    }

    public function testMessageIncludesUnixSocket()
    {
        $pdoException = new PDOException('SQLSTATE[HY000] [2002] No such file or directory');
        $exception = new QueryException('mysql', 'SELECT * FROM users', [], $pdoException, [
            'driver' => 'mysql',
            'unix_socket' => '/tmp/mysql.sock',
            'database' => 'laravel_db',
        ]);

        $this->assertStringContainsString('Socket: /tmp/mysql.sock', $exception->getMessage());
        $this->assertStringContainsString('Database: laravel_db', $exception->getMessage());
        $this->assertStringNotContainsString('Host:', $exception->getMessage());
    }

    public function testMessageHandlesArrayHosts()
    {
        $pdoException = new PDOException('SQLSTATE[HY000] [2002] No such file or directory');
        $exception = new QueryException('mysql::read', 'SELECT * FROM users', [], $pdoException, [
            'driver' => 'mysql',
            'host' => ['192.168.1.10', '192.168.1.11'],
            'port' => '3306',
            'database' => 'laravel_db',
        ]);

        $this->assertStringContainsString('Host: 192.168.1.10, 192.168.1.11', $exception->getMessage());
    }

    public function testMessageHandlesEmptyConnectionInfo()
    {
        $pdoException = new PDOException('SQLSTATE[HY000] [2002] No such file or directory');
        $exception = new QueryException('mysql', 'SELECT * FROM users', [], $pdoException, [
            'driver' => 'mysql',
            'host' => '',
            'port' => '',
            'database' => '',
        ]);

        $this->assertStringContainsString('Host: ,', $exception->getMessage());
        $this->assertStringContainsString('Database: ', $exception->getMessage());
    }

    public function testMessageForSqliteOnlyShowsDatabase()
    {
        $pdoException = new PDOException('SQLSTATE[HY000]: General error: 1 no such table');
        $exception = new QueryException('sqlite', 'SELECT * FROM users', [], $pdoException, [
            'driver' => 'sqlite',
            'name' => 'sqlite',
            'host' => null,
            'port' => null,
            'database' => '/path/to/database.sqlite',
            'unix_socket' => null,
        ]);

        $this->assertStringContainsString('Database: /path/to/database.sqlite', $exception->getMessage());
        $this->assertStringNotContainsString('Host:', $exception->getMessage());
        $this->assertStringNotContainsString('Port:', $exception->getMessage());
    }

    public function testGetConnectionInfoReturnsConnectionInfo()
    {
        $pdoException = new PDOException('Mock error');
        $connectionInfo = [
            'driver' => 'mysql',
            'name' => 'mysql::read',
            'host' => '192.168.1.10',
            'port' => '3306',
            'database' => 'laravel_db',
            'unix_socket' => null,
        ];
        $exception = new QueryException('mysql::read', 'SELECT * FROM users', [], $pdoException, $connectionInfo);

        $this->assertSame($connectionInfo, $exception->getConnectionDetails());
    }

    public function testBackwardCompatibilityWithoutConnectionInfo()
    {
        $pdoException = new PDOException('Mock SQL error');
        $exception = new QueryException('mysql', 'SELECT * FROM users WHERE id = ?', [1], $pdoException);

        $this->assertSame('Mock SQL error (Connection: mysql, SQL: SELECT * FROM users WHERE id = 1)', $exception->getMessage());
        $this->assertSame([], $exception->getConnectionDetails());
    }

    protected function getConnection()
    {
        $connection = m::mock(Connection::class);

        $grammar = new Grammar($connection);

        $connection->shouldReceive('getName')->andReturn('default');
        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);
        $connection->shouldReceive('escape')->with(1, false)->andReturn(1);
        $connection->shouldReceive('escape')->with('br', false)->andReturn("'br'");

        return $connection;
    }
}
