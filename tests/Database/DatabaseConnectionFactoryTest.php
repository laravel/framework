<?php

namespace Illuminate\Tests\Database;

use PDO;
use Mockery as m;
use ReflectionProperty;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connectors\ConnectionFactory;

class DatabaseConnectionFactoryTest extends TestCase
{
    protected $db;

    protected function setUp(): void
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

    protected function tearDown(): void
    {
        m::close();
    }

    public function testConnectionCanBeCreated()
    {
        $this->assertInstanceOf(PDO::class, $this->db->connection()->getPdo());
        $this->assertInstanceOf(PDO::class, $this->db->connection()->getReadPdo());
        $this->assertInstanceOf(PDO::class, $this->db->connection('read_write')->getPdo());
        $this->assertInstanceOf(PDO::class, $this->db->connection('read_write')->getReadPdo());
    }

    public function testSingleConnectionNotCreatedUntilNeeded()
    {
        $connection = $this->db->connection();
        $pdo = new ReflectionProperty(get_class($connection), 'pdo');
        $pdo->setAccessible(true);
        $readPdo = new ReflectionProperty(get_class($connection), 'readPdo');
        $readPdo->setAccessible(true);

        $this->assertNotInstanceOf(PDO::class, $pdo->getValue($connection));
        $this->assertNotInstanceOf(PDO::class, $readPdo->getValue($connection));
    }

    public function testReadWriteConnectionsNotCreatedUntilNeeded()
    {
        $connection = $this->db->connection('read_write');
        $pdo = new ReflectionProperty(get_class($connection), 'pdo');
        $pdo->setAccessible(true);
        $readPdo = new ReflectionProperty(get_class($connection), 'readPdo');
        $readPdo->setAccessible(true);

        $this->assertNotInstanceOf(PDO::class, $pdo->getValue($connection));
        $this->assertNotInstanceOf(PDO::class, $readPdo->getValue($connection));
    }

    public function testIfDriverIsntSetExceptionIsThrown()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A driver must be specified.');

        $factory = new ConnectionFactory($container = m::mock(Container::class));
        $factory->createConnector(['foo']);
    }

    public function testExceptionIsThrownOnUnsupportedDriver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported driver [foo]');

        $factory = new ConnectionFactory($container = m::mock(Container::class));
        $container->shouldReceive('bound')->once()->andReturn(false);
        $factory->createConnector(['driver' => 'foo']);
    }

    public function testCustomConnectorsCanBeResolvedViaContainer()
    {
        $factory = new ConnectionFactory($container = m::mock(Container::class));
        $container->shouldReceive('bound')->once()->with('db.connector.foo')->andReturn(true);
        $container->shouldReceive('make')->once()->with('db.connector.foo')->andReturn('connector');

        $this->assertEquals('connector', $factory->createConnector(['driver' => 'foo']));
    }

    public function testSqliteForeignKeyConstraints()
    {
        $this->db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'foreign_key_constraints' => true,
        ], 'constraints_set');

        $this->assertEquals(0, $this->db->connection()->select('PRAGMA foreign_keys')[0]->foreign_keys);

        $this->assertEquals(1, $this->db->connection('constraints_set')->select('PRAGMA foreign_keys')[0]->foreign_keys);
    }
}
