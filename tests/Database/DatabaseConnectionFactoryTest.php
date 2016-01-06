<?php

use Mockery as m;
use Illuminate\Database\Connection;
use Illuminate\Database\Capsule\Manager as DB;

class DatabaseConnectionFactoryTest extends PHPUnit_Framework_TestCase
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
            ]
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

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIfDriverIsntSetExceptionIsThrown()
    {
        $factory = new Illuminate\Database\Connectors\ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $factory->createConnector(['foo']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownOnUnsupportedDriver()
    {
        $factory = new Illuminate\Database\Connectors\ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('bound')->once()->andReturn(false);
        $factory->createConnector(['driver' => 'foo']);
    }

    public function testCustomConnectorsCanBeResolvedViaContainer()
    {
        $factory = new Illuminate\Database\Connectors\ConnectionFactory($container = m::mock('Illuminate\Container\Container'));
        $container->shouldReceive('bound')->once()->with('db.connector.foo')->andReturn(true);
        $container->shouldReceive('make')->once()->with('db.connector.foo')->andReturn('connector');

        $this->assertEquals('connector', $factory->createConnector(['driver' => 'foo']));
    }
}
