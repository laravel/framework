<?php

namespace Illuminate\Tests\Database\MySql;

use Illuminate\Database\Connectors\MySqlConnector;
use Mockery as m;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class DatabaseConnectorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * @dataProvider connectProvider
     */
    public function testConnectCallsCreateConnectionWithProperArguments($dsn, $config)
    {
        $connector = $this->getMockBuilder(MySqlConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(PDO::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\' collate \'utf8_unicode_ci\'')->andReturn($statement);
        $statement->shouldReceive('execute')->once();
        $connection->shouldReceive('exec')->zeroOrMoreTimes();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function connectProvider()
    {
        return [
            ['mysql:host=foo;dbname=bar', ['host' => 'foo', 'database' => 'bar', 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8']],
            ['mysql:host=foo;port=111;dbname=bar', ['host' => 'foo', 'database' => 'bar', 'port' => 111, 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8']],
            ['mysql:unix_socket=baz;dbname=bar', ['host' => 'foo', 'database' => 'bar', 'port' => 111, 'unix_socket' => 'baz', 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8']],
        ];
    }

    public function testConnectCallsCreateConnectionWithIsolationLevel()
    {
        $dsn = 'mysql:host=foo;dbname=bar';
        $config = ['host' => 'foo', 'database' => 'bar', 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8', 'isolation_level' => 'REPEATABLE READ'];

        $connector = $this->getMockBuilder(MySqlConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(PDO::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\' collate \'utf8_unicode_ci\'')->andReturn($statement);
        $connection->shouldReceive('prepare')->once()->with('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ')->andReturn($statement);
        $statement->shouldReceive('execute')->zeroOrMoreTimes();
        $connection->shouldReceive('exec')->zeroOrMoreTimes();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }
}
