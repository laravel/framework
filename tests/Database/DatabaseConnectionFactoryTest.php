<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connectors\ConnectionFactory;
use InvalidArgumentException;
use Mockery as m;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

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
            'url' => 'sqlite:///:memory:',
        ], 'url');

        $this->db->addConnection([
            'driver' => 'sqlite',
            'read' => [
                'database' => ':memory:',
            ],
            'write' => [
                'database' => ':memory:',
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
        $this->assertInstanceOf(PDO::class, $this->db->getConnection()->getPdo());
        $this->assertInstanceOf(PDO::class, $this->db->getConnection()->getReadPdo());
        $this->assertInstanceOf(PDO::class, $this->db->getConnection('read_write')->getPdo());
        $this->assertInstanceOf(PDO::class, $this->db->getConnection('read_write')->getReadPdo());
        $this->assertInstanceOf(PDO::class, $this->db->getConnection('url')->getPdo());
        $this->assertInstanceOf(PDO::class, $this->db->getConnection('url')->getReadPdo());
    }

    public function testConnectionFromUrlHasProperConfig()
    {
        $this->db->addConnection([
            'url' => 'mysql://root:pass@db/local?strict=true',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
        ], 'url-config');

        $this->assertEquals([
            'name' => 'url-config',
            'driver' => 'mysql',
            'database' => 'local',
            'host' => 'db',
            'username' => 'root',
            'password' => 'pass',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ], $this->db->getConnection('url-config')->getConfig());
    }

    public function testSingleConnectionNotCreatedUntilNeeded()
    {
        $connection = $this->db->getConnection();
        $pdo = new ReflectionProperty(get_class($connection), 'pdo');
        $pdo->setAccessible(true);
        $readPdo = new ReflectionProperty(get_class($connection), 'readPdo');
        $readPdo->setAccessible(true);

        $this->assertNotInstanceOf(PDO::class, $pdo->getValue($connection));
        $this->assertNotInstanceOf(PDO::class, $readPdo->getValue($connection));
    }

    public function testReadWriteConnectionsNotCreatedUntilNeeded()
    {
        $connection = $this->db->getConnection('read_write');
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

        $this->assertSame('connector', $factory->createConnector(['driver' => 'foo']));
    }

    public function testSqliteForeignKeyConstraints()
    {
        $this->db->addConnection([
            'url' => 'sqlite:///:memory:?foreign_key_constraints=true',
        ], 'constraints_set');

        $this->assertEquals(0, $this->db->getConnection()->select('PRAGMA foreign_keys')[0]->foreign_keys);

        $this->assertEquals(1, $this->db->getConnection('constraints_set')->select('PRAGMA foreign_keys')[0]->foreign_keys);
    }
}
