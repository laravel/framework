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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
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

    #[DataProvider('mySqlConnectProvider')]
    public function testMySqlConnectCallsCreateConnectionWithProperArguments($dsn, $config)
    {
        $connector = $this->getMockBuilder(MySqlConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(PDO::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $connection->shouldReceive('exec')->once()->with('use `bar`;')->andReturn(true);
        $connection->shouldReceive('exec')->once()->with("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci';")->andReturn(true);
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public static function mySqlConnectProvider()
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
        $connection->shouldReceive('exec')->once()->with('use `bar`;')->andReturn(true);
        $connection->shouldReceive('exec')->once()->with('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ;')->andReturn(true);
        $connection->shouldReceive('exec')->once()->with("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci';")->andReturn(true);
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testPostgresConnectCallsCreateConnectionWithProperArguments()
    {
        $dsn = 'pgsql:host=foo;dbname=\'bar\';port=111;client_encoding=\'utf8\'';
        $config = ['host' => 'foo', 'database' => 'bar', 'port' => 111, 'charset' => 'utf8'];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->zeroOrMoreTimes()->andReturn($statement);
        $statement->shouldReceive('execute')->zeroOrMoreTimes();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    /**
     * @param  string  $searchPath
     * @param  string  $expectedSql
     */
    #[DataProvider('provideSearchPaths')]
    public function testPostgresSearchPathIsSet($searchPath, $expectedSql)
    {
        $dsn = 'pgsql:host=foo;dbname=\'bar\';client_encoding=\'utf8\'';
        $config = ['host' => 'foo', 'database' => 'bar', 'search_path' => $searchPath, 'charset' => 'utf8'];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->once()->with($expectedSql)->andReturn($statement);
        $statement->shouldReceive('execute')->once();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public static function provideSearchPaths()
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

    public function testPostgresSearchPathFallbackToConfigKeySchema()
    {
        $dsn = 'pgsql:host=foo;dbname=\'bar\';client_encoding=\'utf8\'';
        $config = ['host' => 'foo', 'database' => 'bar', 'schema' => ['public', '"user"'], 'charset' => 'utf8'];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->once()->with('set search_path to "public", "user"')->andReturn($statement);
        $statement->shouldReceive('execute')->once();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testPostgresApplicationNameIsSet()
    {
        $dsn = 'pgsql:host=foo;dbname=\'bar\';client_encoding=\'utf8\';application_name=\'Laravel App\'';
        $config = ['host' => 'foo', 'database' => 'bar', 'charset' => 'utf8', 'application_name' => 'Laravel App'];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->zeroOrMoreTimes()->andReturn($statement);
        $statement->shouldReceive('execute')->zeroOrMoreTimes();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testPostgresApplicationUseAlternativeDatabaseName()
    {
        $dsn = 'pgsql:dbname=\'baz\'';
        $config = ['database' => 'bar', 'connect_via_database' => 'baz'];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->zeroOrMoreTimes()->andReturn($statement);
        $statement->shouldReceive('execute')->zeroOrMoreTimes();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testPostgresApplicationUseAlternativeDatabaseNameAndPort()
    {
        $dsn = 'pgsql:dbname=\'baz\';port=2345';
        $config = ['database' => 'bar', 'connect_via_database' => 'baz', 'port' => 5432, 'connect_via_port' => 2345];
        $connector = $this->getMockBuilder(PostgresConnector::class)->onlyMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock(stdClass::class);
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->willReturn(['options']);
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->willReturn($connection);
        $statement = m::mock(PDOStatement::class);
        $connection->shouldReceive('prepare')->zeroOrMoreTimes()->andReturn($statement);
        $statement->shouldReceive('execute')->zeroOrMoreTimes();
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

    public function testSQLiteNamedMemoryDatabasesMayBeConnectedTo()
    {
        $dsn = 'sqlite:file:mydb?mode=memory&cache=shared';
        $config = ['database' => 'file:mydb?mode=memory&cache=shared'];
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

    #[RequiresPhpExtension('odbc')]
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
