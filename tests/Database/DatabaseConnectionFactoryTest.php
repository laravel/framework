<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use ReflectionProperty;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connectors\ConnectionFactory;

class DatabaseConnectionFactoryTest extends TestCase
{
    protected $db;

    public function setUp()
    {
        $this->db = new DB;

        $this->db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $this->db->addConnection([
            'driver' => 'sqlite',
            'read' => [
                'database'  => ':memory:',
            ],
            'write' => [
                'database'  => ':memory:',
            ],
        ], 'read_write');

        $this->db->setAsGlobal();
    }

    public function tearDown()
    {
        m::close();
    }

    public function testConnectionCanBeCreated()
    {
        $this->assertInstanceOf('PDO', $this->db->connection()->getPdo());
        $this->assertInstanceOf('PDO', $this->db->connection()->getReadPdo());
        $this->assertInstanceOf('PDO', $this->db->connection('read_write')->getPdo());
        $this->assertInstanceOf('PDO', $this->db->connection('read_write')->getReadPdo());
    }

    public function testSingleConnectionNotCreatedUntilNeeded()
    {
        $connection = $this->db->connection();
        $pdo = new ReflectionProperty(get_class($connection), 'pdo');
        $pdo->setAccessible(true);
        $readPdo = new ReflectionProperty(get_class($connection), 'readPdo');
        $readPdo->setAccessible(true);

        $this->assertNotInstanceOf('PDO', $pdo->getValue($connection));
        $this->assertNotInstanceOf('PDO', $readPdo->getValue($connection));
    }

    public function testReadWriteConnectionsNotCreatedUntilNeeded()
    {
        $connection = $this->db->connection('read_write');
        $pdo = new ReflectionProperty(get_class($connection), 'pdo');
        $pdo->setAccessible(true);
        $readPdo = new ReflectionProperty(get_class($connection), 'readPdo');
        $readPdo->setAccessible(true);

        $this->assertNotInstanceOf('PDO', $pdo->getValue($connection));
        $this->assertNotInstanceOf('PDO', $readPdo->getValue($connection));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIfDriverIsntSetExceptionIsThrown()
    {
        $factory = new ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $factory->createConnector(['foo']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownOnUnsupportedDriver()
    {
        $factory = new ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('bound')->once()->andReturn(false);
        $factory->createConnector(['driver' => 'foo']);
    }

    public function testCustomConnectorsCanBeResolvedViaContainer()
    {
        $factory = new ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('bound')->once()->with('db.connector.foo')->andReturn(true);
        $container->shouldReceive('make')->once()->with('db.connector.foo')->andReturn('connector');

        $this->assertEquals('connector', $factory->createConnector(['driver' => 'foo']));
    }
}
