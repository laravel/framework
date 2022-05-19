<?php

namespace Illuminate\Tests\Database\Postgres;

use Illuminate\Database\Connectors\PostgresConnector;
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

    public function testConnectCallsCreateConnectionWithProperArguments()
    {
        $dsn = 'pgsql:host=foo;dbname=\'bar\';port=111';
        $config = ['host' => 'foo', 'database' => 'bar', 'port' => 111, 'charset' => 'utf8'];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\'')->andReturn($statement);
        $statement->shouldReceive('execute')->once();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    /**
     * @dataProvider provideSearchPaths
     *
     * @param  string  $searchPath
     * @param  string  $expectedSql
     */
    public function testSearchPathIsSet($searchPath, $expectedSql)
    {
        $dsn = 'pgsql:host=foo;dbname=\'bar\'';
        $config = ['host' => 'foo', 'database' => 'bar', 'search_path' => $searchPath, 'charset' => 'utf8'];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\'')->andReturn($statement);
        $connection->shouldReceive('prepare')->once()->with($expectedSql)->andReturn($statement);
        $statement->shouldReceive('execute')->twice();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function provideSearchPaths()
    {
        return [
            'all-lowercase' => [
                'public',
                'set search_path to "public"',
            ],
            'case-sensitive' => [
                'Public',
                'set search_path to "Public"',
            ],
            'special characters' => [
                '¡foo_bar-Baz!.Áüõß',
                'set search_path to "¡foo_bar-Baz!.Áüõß"',
            ],
            'single-quoted' => [
                "'public'",
                'set search_path to "public"',
            ],
            'double-quoted' => [
                '"public"',
                'set search_path to "public"',
            ],
            'variable' => [
                '$user',
                'set search_path to "$user"',
            ],
            'delimit space' => [
                'public user',
                'set search_path to "public", "user"',
            ],
            'delimit newline' => [
                "public\nuser\r\n\ttest",
                'set search_path to "public", "user", "test"',
            ],
            'delimit comma' => [
                'public,user',
                'set search_path to "public", "user"',
            ],
            'delimit comma and space' => [
                'public, user',
                'set search_path to "public", "user"',
            ],
            'single-quoted many' => [
                "'public', 'user'",
                'set search_path to "public", "user"',
            ],
            'double-quoted many' => [
                '"public", "user"',
                'set search_path to "public", "user"',
            ],
            'quoted space is unsupported in string' => [
                '"public user"',
                'set search_path to "public", "user"',
            ],
            'array' => [
                ['public', 'user'],
                'set search_path to "public", "user"',
            ],
            'array with variable' => [
                ['public', '$user'],
                'set search_path to "public", "$user"',
            ],
            'array with delimiter characters' => [
                ['public', '"user"', "'test'", 'spaced schema'],
                'set search_path to "public", "user", "test", "spaced schema"',
            ],
        ];
    }

    public function testSearchPathFallbackToConfigKeySchema()
    {
        $dsn = 'pgsql:host=foo;dbname=\'bar\'';
        $config = ['host' => 'foo', 'database' => 'bar', 'schema' => ['public', '"user"'], 'charset' => 'utf8'];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\'')->andReturn($statement);
        $connection->shouldReceive('prepare')->once()->with('set search_path to "public", "user"')->andReturn($statement);
        $statement->shouldReceive('execute')->twice();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testApplicationNameIsSet()
    {
        $dsn = 'pgsql:host=foo;dbname=\'bar\'';
        $config = ['host' => 'foo', 'database' => 'bar', 'charset' => 'utf8', 'application_name' => 'Laravel App'];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\'')->andReturn($statement);
        $connection->shouldReceive('prepare')->once()->with('set application_name to \'Laravel App\'')->andReturn($statement);
        $statement->shouldReceive('execute')->twice();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testConnectorReadsIsolationLevelFromConfig()
    {
        $dsn = 'pgsql:host=foo;dbname=\'bar\';port=111';
        $config = ['host' => 'foo', 'database' => 'bar', 'port' => 111, 'isolation_level' => 'SERIALIZABLE'];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(PDO::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->once()->with('set session characteristics as transaction isolation level SERIALIZABLE')->andReturn($statement);
        $statement->shouldReceive('execute')->zeroOrMoreTimes();
        $connection->shouldReceive('exec')->zeroOrMoreTimes();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }
}
