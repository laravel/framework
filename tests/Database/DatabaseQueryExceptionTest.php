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
