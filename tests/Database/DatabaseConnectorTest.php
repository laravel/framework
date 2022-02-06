<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connectors\Connector;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Database\Connectors\SqlServerConnector;
use Mockery as m;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseConnectorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testOptionResolution()
    {
        $connector = new Connector;
        $connector->setDefaultOptions([0 => 'foo', 1 => 'bar']);
        $this->assertEquals([0 => 'baz', 1 => 'bar', 2 => 'boom'], $connector->getOptions(['options' => [0 => 'baz', 2 => 'boom']]));
    }

    /**
     * @dataProvider mySqlConnectProvider
     */
    public function testMySqlConnectCallsCreateConnectionWithProperArguments($dsn, $config)
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

    public function mySqlConnectProvider()
    {
        return [
            ['mysql:host=foo;dbname=bar', ['host' => 'foo', 'database' => 'bar', 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8']],
            ['mysql:host=foo;port=111;dbname=bar', ['host' => 'foo', 'database' => 'bar', 'port' => 111, 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8']],
            ['mysql:unix_socket=baz;dbname=bar', ['host' => 'foo', 'database' => 'bar', 'port' => 111, 'unix_socket' => 'baz', 'collation' => 'utf8_unicode_ci', 'charset' => 'utf8']],
        ];
    }

    public function testMySqlConnectCallsCreateConnectionWithIsolationLevel()
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

    public function testPostgresConnectCallsCreateConnectionWithProperArguments()
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

    public function testPostgresSearchPathIsSet()
    {
        $dsn = 'pgsql:host=foo;dbname=\'bar\'';
        $config = ['host' => 'foo', 'database' => 'bar', 'schema' => 'public', 'charset' => 'utf8'];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\'')->andReturn($statement);
        $connection->shouldReceive('prepare')->once()->with('set search_path to "public"')->andReturn($statement);
        $statement->shouldReceive('execute')->twice();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testPostgresSearchPathArraySupported()
    {
        $dsn = 'pgsql:host=foo;dbname=\'bar\'';
        $config = ['host' => 'foo', 'database' => 'bar', 'schema' => ['public', 'user'], 'charset' => 'utf8'];
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

    public function testPostgresApplicationNameIsSet()
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

    public function testPostgresConnectorReadsIsolationLevelFromConfig()
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

    public function testSQLiteMemoryDatabasesMayBeConnectedTo()
    {
        $dsn = 'sqlite::memory:';
        $config = ['database' => ':memory:'];
        $connector = $this->getMockBuilder(SQLiteConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testSQLiteFileDatabasesMayBeConnectedTo()
    {
        $dsn = 'sqlite:'.__DIR__;
        $config = ['database' => __DIR__];
        $connector = $this->getMockBuilder(SQLiteConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testSqlServerConnectCallsCreateConnectionWithProperArguments()
    {
        $config = ['host' => 'foo', 'database' => 'bar', 'port' => 111];
        $dsn = $this->getDsn($config);
        $connector = $this->getMockBuilder(SqlServerConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testSqlServerConnectCallsCreateConnectionWithOptionalArguments()
    {
        $config = ['host' => 'foo', 'database' => 'bar', 'port' => 111, 'readonly' => true, 'charset' => 'utf-8', 'pooling' => false, 'appname' => 'baz'];
        $dsn = $this->getDsn($config);
        $connector = $this->getMockBuilder(SqlServerConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    /**
     * @requires extension odbc
     */
    public function testSqlServerConnectCallsCreateConnectionWithPreferredODBC()
    {
        $config = ['odbc' => true, 'odbc_datasource_name' => 'server=localhost;database=test;'];
        $dsn = $this->getDsn($config);
        $connector = $this->getMockBuilder(SqlServerConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    protected function getDsn(array $config)
    {
        extract($config, EXTR_SKIP);

        $availableDrivers = PDO::getAvailableDrivers();

        if (in_array('odbc', $availableDrivers) &&
            ($config['odbc'] ?? null) === true) {
            return isset($config['odbc_datasource_name'])
                ? 'odbc:'.$config['odbc_datasource_name'] : '';
        }

        if (in_array('sqlsrv', $availableDrivers)) {
            $port = isset($config['port']) ? ','.$port : '';
            $appname = isset($config['appname']) ? ';APP='.$config['appname'] : '';
            $readonly = isset($config['readonly']) ? ';ApplicationIntent=ReadOnly' : '';
            $pooling = (isset($config['pooling']) && $config['pooling'] == false) ? ';ConnectionPooling=0' : '';

            return "sqlsrv:Server={$host}{$port};Database={$database}{$readonly}{$pooling}{$appname}";
        } else {
            $port = isset($config['port']) ? ':'.$port : '';
            $appname = isset($config['appname']) ? ';appname='.$config['appname'] : '';
            $charset = isset($config['charset']) ? ';charset='.$config['charset'] : '';

            return "dblib:host={$host}{$port};dbname={$database}{$charset}{$appname}";
        }
    }
}
