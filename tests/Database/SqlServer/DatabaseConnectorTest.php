<?php

namespace Illuminate\Tests\Database\SqlServer;

use Illuminate\Database\Connectors\SqlServerConnector;
use Mockery as m;
use PDO;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseConnectorTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testConnectCallsCreateConnectionWithProperArguments()
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

    public function testConnectCallsCreateConnectionWithOptionalArguments()
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
    public function testConnectCallsCreateConnectionWithPreferredODBC()
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
