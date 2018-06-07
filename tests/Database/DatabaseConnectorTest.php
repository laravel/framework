<?php

namespace Illuminate\Tests\Database;

use PDO;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseConnectorTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testOptionResolution()
    {
        $connector = new \Illuminate\Database\Connectors\Connector;
        $connector->setDefaultOptions([0 => 'foo', 1 => 'bar']);
        $this->assertEquals([0 => 'baz', 1 => 'bar', 2 => 'boom'], $connector->getOptions(['options' => [0 => 'baz', 2 => 'boom']]));
    }

    /**
     * @dataProvider mySqlConnectProvider
     */
    public function testMySqlConnectCallsCreateConnectionWithProperArguments($dsn, $config)
    {
        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\MySqlConnector')->setMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock('PDO');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(['options']));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->will($this->returnValue($connection));
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\' collate \'utf8_unicode_ci\'')->andReturn($connection);
        $connection->shouldReceive('execute')->once();
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

    public function testPostgresConnectCallsCreateConnectionWithProperArguments()
    {
        $dsn = 'pgsql:host=foo;dbname=bar;port=111';
        $config = ['host' => 'foo', 'database' => 'bar', 'port' => 111, 'charset' => 'utf8'];
        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\PostgresConnector')->setMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(['options']));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->will($this->returnValue($connection));
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\'')->andReturn($connection);
        $connection->shouldReceive('execute')->once();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testPostgresSearchPathIsSet()
    {
        $dsn = 'pgsql:host=foo;dbname=bar';
        $config = ['host' => 'foo', 'database' => 'bar', 'schema' => 'public', 'charset' => 'utf8'];
        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\PostgresConnector')->setMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(['options']));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->will($this->returnValue($connection));
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\'')->andReturn($connection);
        $connection->shouldReceive('prepare')->once()->with('set search_path to "public"')->andReturn($connection);
        $connection->shouldReceive('execute')->twice();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testPostgresSearchPathArraySupported()
    {
        $dsn = 'pgsql:host=foo;dbname=bar';
        $config = ['host' => 'foo', 'database' => 'bar', 'schema' => ['public', 'user'], 'charset' => 'utf8'];
        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\PostgresConnector')->setMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(['options']));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->will($this->returnValue($connection));
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\'')->andReturn($connection);
        $connection->shouldReceive('prepare')->once()->with('set search_path to "public", "user"')->andReturn($connection);
        $connection->shouldReceive('execute')->twice();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testPostgresApplicationNameIsSet()
    {
        $dsn = 'pgsql:host=foo;dbname=bar';
        $config = ['host' => 'foo', 'database' => 'bar', 'charset' => 'utf8', 'application_name' => 'Laravel App'];
        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\PostgresConnector')->setMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(['options']));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->will($this->returnValue($connection));
        $connection->shouldReceive('prepare')->once()->with('set names \'utf8\'')->andReturn($connection);
        $connection->shouldReceive('prepare')->once()->with('set application_name to \'Laravel App\'')->andReturn($connection);
        $connection->shouldReceive('execute')->twice();
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testSQLiteMemoryDatabasesMayBeConnectedTo()
    {
        $dsn = 'sqlite::memory:';
        $config = ['database' => ':memory:'];
        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\SQLiteConnector')->setMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(['options']));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->will($this->returnValue($connection));
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testSQLiteFileDatabasesMayBeConnectedTo()
    {
        $dsn = 'sqlite:'.__DIR__;
        $config = ['database' => __DIR__];
        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\SQLiteConnector')->setMethods(['createConnection', 'getOptions'])->getMock();
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(['options']));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->will($this->returnValue($connection));
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testSqlServerConnectCallsCreateConnectionWithProperArguments()
    {
        $config = ['host' => 'foo', 'database' => 'bar', 'port' => 111];
        $dsn = $this->getDsn($config);
        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\SqlServerConnector')->setMethods(['createConnection', 'getOptions', 'getAvailableDrivers'])->getMock();
        $connector->expects($this->atLeastOnce())->method('getAvailableDrivers')->will($this->returnValue(['dblib']));
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(['options']));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->will($this->returnValue($connection));
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testSqlServerConnectCallsCreateConnectionWithOptionalArguments()
    {
        $config = ['host' => 'foo', 'database' => 'bar', 'port' => 111, 'readonly' => true, 'charset' => 'utf-8', 'pooling' => false, 'appname' => 'baz'];
        $dsn = $this->getDsn($config);
        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\SqlServerConnector')->setMethods(['createConnection', 'getOptions', 'getAvailableDrivers'])->getMock();
        $connector->expects($this->atLeastOnce())->method('getAvailableDrivers')->will($this->returnValue(['dblib']));
        $connection = m::mock('stdClass');
        $connector->expects($this->once())->method('getOptions')->with($this->equalTo($config))->will($this->returnValue(['options']));
        $connector->expects($this->once())->method('createConnection')->with($this->equalTo($dsn), $this->equalTo($config), $this->equalTo(['options']))->will($this->returnValue($connection));
        $result = $connector->connect($config);

        $this->assertSame($result, $connection);
    }

    public function testSqlsrvDsnHasMorePriorityWhenSqlrvDriverExists()
    {
        $config = ['host' => 'foo', 'database' => 'bar', 'port' => 111];
        $availableDrivers = ['dblib', 'sqlsrv', 'odbc'];
        $expectedDsn = 'sqlsrv:Server=foo,111;Database=bar';

        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\SqlServerConnector')
            ->setMethods(['createConnection', 'getOptions', 'getAvailableDrivers'])
            ->getMock();

        $connector->expects($this->atLeastOnce())
            ->method('getAvailableDrivers')
            ->will($this->returnValue($availableDrivers));

        $connector->expects($this->once())->method('getOptions')
            ->with($this->equalTo($config))
            ->will($this->returnValue([]));

        $connection = m::mock('stdClass');

        $connector->expects($this->once())
            ->method('createConnection')
            ->with($this->equalTo($expectedDsn), $this->equalTo($config))
            ->will($this->returnValue($connection));

        $result = $connector->connect($config);

        $this->assertEquals($result, $connection);
    }

    public function testDblibDsnIsUsedWhenSqlrvDriverDoesNotExists()
    {
        $config = ['host' => 'foo', 'database' => 'bar', 'port' => 111];
        $availableDrivers = ['dblib', 'odbc'];
        $expectedDsn = 'dblib:host=foo:111;dbname=bar';

        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\SqlServerConnector')
            ->setMethods(['createConnection', 'getOptions', 'getAvailableDrivers'])
            ->getMock();

        $connector->expects($this->atLeastOnce())
            ->method('getAvailableDrivers')
            ->will($this->returnValue($availableDrivers));

        $connector->expects($this->once())->method('getOptions')
            ->with($this->equalTo($config))
            ->will($this->returnValue([]));

        $connection = m::mock('stdClass');

        $connector->expects($this->once())
            ->method('createConnection')
            ->with($this->equalTo($expectedDsn), $this->equalTo($config))
            ->will($this->returnValue($connection));

        $result = $connector->connect($config);

        $this->assertEquals($result, $connection);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testItThrowsARuntimeExceptionIfNoDriverWasFound()
    {
        $config = ['host' => 'foo', 'database' => 'bar', 'port' => 111];
        $availableDrivers = [];

        $connector = $this->getMockBuilder('Illuminate\Database\Connectors\SqlServerConnector')
            ->setMethods(['createConnection', 'getOptions', 'getAvailableDrivers'])
            ->getMock();

        $connector->expects($this->atLeastOnce())
            ->method('getAvailableDrivers')
            ->will($this->returnValue($availableDrivers));

        $connector->expects($this->once())->method('getOptions')
            ->with($this->equalTo($config))
            ->will($this->returnValue([]));

        $connection = m::mock('stdClass');

        $connector->expects($this->never())->method('createConnection');

        $result = $connector->connect($config);
    }

    protected function getDsn(array $config)
    {
        extract($config, EXTR_SKIP);

        if (in_array('sqlsrv', PDO::getAvailableDrivers())) {
            $port = isset($config['port']) ? ',' . $port : '';
            $appname = isset($config['appname']) ? ';APP=' . $config['appname'] : '';
            $readonly = isset($config['readonly']) ? ';ApplicationIntent=ReadOnly' : '';
            $pooling = (isset($config['pooling']) && $config['pooling'] == false) ? ';ConnectionPooling=0' : '';

            return "sqlsrv:Server={$host}{$port};Database={$database}{$readonly}{$pooling}{$appname}";
        } else {
            $port = isset($config['port']) ? ':' . $port : '';
            $appname = isset($config['appname']) ? ';appname=' . $config['appname'] : '';
            $charset = isset($config['charset']) ? ';charset=' . $config['charset'] : '';

            return "dblib:host={$host}{$port};dbname={$database}{$charset}{$appname}";
        }
    }
}
