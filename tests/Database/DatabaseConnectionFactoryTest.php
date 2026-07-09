<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connectors\ConnectionFactory;
use InvalidArgumentException;
use Mockery as m;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
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
        $readPdo = new ReflectionProperty(get_class($connection), 'readPdo');

        $this->assertNotInstanceOf(PDO::class, $pdo->getValue($connection));
        $this->assertNotInstanceOf(PDO::class, $readPdo->getValue($connection));
    }

    public function testReadWriteConnectionsNotCreatedUntilNeeded()
    {
        $connection = $this->db->getConnection('read_write');
        $pdo = new ReflectionProperty(get_class($connection), 'pdo');
        $readPdo = new ReflectionProperty(get_class($connection), 'readPdo');

        $this->assertNotInstanceOf(PDO::class, $pdo->getValue($connection));
        $this->assertNotInstanceOf(PDO::class, $readPdo->getValue($connection));
    }

    public function testReadWriteConnectionSetsReadPdoConfig()
    {
        $connection = $this->db->getConnection('read_write');

        $readPdoConfig = new ReflectionProperty(get_class($connection), 'readPdoConfig');

        $config = $readPdoConfig->getValue($connection);

        $this->assertNotEmpty($config);
        $this->assertArrayHasKey('database', $config);
        $this->assertSame(':memory:', $config['database']);
    }

    public function testPostgresDirectConnectionConfigurationIsAttached()
    {
        $this->db->addConnection([
            'driver' => 'pgsql',
            'host' => 'pooler-host',
            'port' => '6432',
            'database' => 'laravel',
            'username' => 'pooler-user',
            'password' => 'pooler-password',
            'prefix' => '',
            'connect_via_database' => 'pooler_database',
            'connect_via_port' => '6432',
            'direct' => [
                'host' => 'direct-host',
                'port' => '5432',
                'username' => 'direct-user',
                'password' => 'direct-password',
                'sslmode' => 'require',
            ],
        ], 'pooled_pgsql');

        $connection = $this->db->getConnection('pooled_pgsql');
        $directPdo = new ReflectionProperty(get_class($connection), 'directPdo');

        $this->assertTrue($connection->hasDirectConnection());
        $this->assertNotInstanceOf(PDO::class, $directPdo->getValue($connection));
        $this->assertTrue($connection->getConfig('pooled'));
        $this->assertTrue($connection->getConfig('options')[PDO::ATTR_EMULATE_PREPARES]);

        $directConfig = $connection->getDirectPdoConfig();

        $this->assertSame('direct-host', $directConfig['host']);
        $this->assertSame('5432', $directConfig['port']);
        $this->assertSame('direct-user', $directConfig['username']);
        $this->assertSame('direct-password', $directConfig['password']);
        $this->assertSame('require', $directConfig['sslmode']);
        $this->assertSame('laravel', $directConfig['database']);
        $this->assertFalse($directConfig['options'][PDO::ATTR_EMULATE_PREPARES]);
        $this->assertArrayNotHasKey('connect_via_database', $directConfig);
        $this->assertArrayNotHasKey('connect_via_port', $directConfig);
    }

    public function testPostgresDirectConnectionConfigurationInheritsBaseCredentialsWhenNotConfigured()
    {
        $this->db->addConnection([
            'driver' => 'pgsql',
            'host' => 'pooler-host',
            'port' => '6432',
            'database' => 'laravel',
            'username' => 'pooler-user',
            'password' => 'pooler-password',
            'prefix' => '',
            'direct' => [
                'host' => 'direct-host',
                'port' => '5432',
            ],
        ], 'pooled_pgsql_inherited_credentials');

        $directConfig = $this->db->getConnection('pooled_pgsql_inherited_credentials')->getDirectPdoConfig();

        $this->assertSame('pooler-user', $directConfig['username']);
        $this->assertSame('pooler-password', $directConfig['password']);
    }

    public function testPostgresDirectConnectionConfigurationCanOverridePortAndUsernameWithoutHost()
    {
        $this->db->addConnection([
            'driver' => 'pgsql',
            'host' => 'same-host',
            'port' => '6432',
            'database' => 'laravel',
            'username' => 'pooler-user|pooler',
            'password' => 'shared-password',
            'prefix' => '',
            'direct' => [
                'port' => '5432',
                'username' => 'direct-user',
            ],
        ], 'pooled_pgsql_same_host');

        $directConfig = $this->db->getConnection('pooled_pgsql_same_host')->getDirectPdoConfig();

        $this->assertSame('same-host', $directConfig['host']);
        $this->assertSame('5432', $directConfig['port']);
        $this->assertSame('direct-user', $directConfig['username']);
        $this->assertSame('shared-password', $directConfig['password']);
    }

    public function testNonPostgresDirectConfigurationIsIgnored()
    {
        $this->db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'direct' => [
                'database' => ':memory:',
            ],
        ], 'sqlite_direct');

        $this->assertFalse($this->db->getConnection('sqlite_direct')->hasDirectConnection());
    }

    #[DataProvider('pooledPostgresEmulatePreparesProvider')]
    public function testPooledPostgresEmulatePreparesPrecedence($baseOption, $directOption, $expectedPooledOption, $expectedDirectOption)
    {
        $config = [
            'driver' => 'pgsql',
            'name' => 'pgsql',
            'host' => 'pooler-host',
            'port' => '6432',
            'database' => 'laravel',
            'username' => 'root',
            'password' => '',
            'prefix' => '',
            'direct' => [
                'host' => 'direct-host',
            ],
        ];

        if (! is_null($baseOption)) {
            $config['options'][PDO::ATTR_EMULATE_PREPARES] = $baseOption;
        }

        if (! is_null($directOption)) {
            $config['direct']['options'][PDO::ATTR_EMULATE_PREPARES] = $directOption;
        }

        $config = $this->callConnectionFactoryMethod('ensurePooledPostgresIsProperlyConfigured', $config);
        $directConfig = $this->callConnectionFactoryMethod('getDirectConfig', $config);

        $this->assertSame($expectedPooledOption, $config['options'][PDO::ATTR_EMULATE_PREPARES]);
        $this->assertSame($expectedDirectOption, $directConfig['options'][PDO::ATTR_EMULATE_PREPARES]);
    }

    public static function pooledPostgresEmulatePreparesProvider()
    {
        return [
            'base missing, direct missing' => [null, null, true, false],
            'base missing, direct true' => [null, true, true, true],
            'base missing, direct false' => [null, false, true, false],
            'base true, direct missing' => [true, null, true, false],
            'base true, direct true' => [true, true, true, true],
            'base true, direct false' => [true, false, true, false],
            'base false, direct missing' => [false, null, false, false],
            'base false, direct true' => [false, true, false, true],
            'base false, direct false' => [false, false, false, false],
        ];
    }

    public function testPooledPostgresOptionsAreAppliedToReadAndWriteConfigurations()
    {
        $config = $this->callConnectionFactoryMethod('ensurePooledPostgresIsProperlyConfigured', [
            'driver' => 'pgsql',
            'name' => 'pgsql',
            'host' => 'pooler-host',
            'database' => 'laravel',
            'username' => 'root',
            'password' => '',
            'prefix' => '',
            'read' => [
                'host' => 'read-pooler-host',
                'options' => [
                    PDO::ATTR_TIMEOUT => 5,
                ],
            ],
            'write' => [[
                'host' => 'write-pooler-host',
                'options' => [
                    PDO::ATTR_TIMEOUT => 10,
                ],
            ]],
            'direct' => [
                'host' => 'direct-host',
            ],
        ]);

        $readConfig = $this->callConnectionFactoryMethod('getReadConfig', $config);
        $writeConfig = $this->callConnectionFactoryMethod('getWriteConfig', $config);
        $directConfig = $this->callConnectionFactoryMethod('getDirectConfig', $config);

        $this->assertSame('read-pooler-host', $readConfig['host']);
        $this->assertSame(5, $readConfig['options'][PDO::ATTR_TIMEOUT]);
        $this->assertTrue($readConfig['options'][PDO::ATTR_EMULATE_PREPARES]);

        $this->assertSame('write-pooler-host', $writeConfig['host']);
        $this->assertSame(10, $writeConfig['options'][PDO::ATTR_TIMEOUT]);
        $this->assertTrue($writeConfig['options'][PDO::ATTR_EMULATE_PREPARES]);

        $this->assertSame('direct-host', $directConfig['host']);
        $this->assertFalse($directConfig['options'][PDO::ATTR_EMULATE_PREPARES]);
        $this->assertArrayNotHasKey('read', $directConfig);
        $this->assertArrayNotHasKey('write', $directConfig);
    }

    public function testPooledPostgresWithoutDirectEndpointEmitsWarning()
    {
        $warning = null;

        set_error_handler(function ($level, $message) use (&$warning) {
            $warning = [$level, $message];

            return true;
        }, E_USER_WARNING);

        try {
            $config = $this->callConnectionFactoryMethod('ensurePooledPostgresIsProperlyConfigured', [
                'driver' => 'pgsql',
                'name' => 'pgsql',
                'host' => 'pooler-host',
                'database' => 'laravel',
                'username' => 'root',
                'password' => '',
                'prefix' => '',
                'pooled' => true,
            ]);
        } finally {
            restore_error_handler();
        }

        $this->assertSame(E_USER_WARNING, $warning[0]);
        $this->assertStringContainsString("sets 'pooled' => true without a 'direct' endpoint", $warning[1]);
        $this->assertTrue($config['options'][PDO::ATTR_EMULATE_PREPARES]);
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

    public function testSqliteBusyTimeout()
    {
        $this->db->addConnection([
            'url' => 'sqlite:///:memory:?busy_timeout=1234',
        ], 'busy_timeout_set');

        // Can't compare to 0, default value may be something else
        $this->assertNotSame(1234, $this->db->getConnection()->select('PRAGMA busy_timeout')[0]->timeout);

        $this->assertSame(1234, $this->db->getConnection('busy_timeout_set')->select('PRAGMA busy_timeout')[0]->timeout);
    }

    public function testSqliteSynchronous()
    {
        $this->db->addConnection([
            'url' => 'sqlite:///:memory:?synchronous=NORMAL',
        ], 'synchronous_set');

        $this->assertSame(2, $this->db->getConnection()->select('PRAGMA synchronous')[0]->synchronous);

        $this->assertSame(1, $this->db->getConnection('synchronous_set')->select('PRAGMA synchronous')[0]->synchronous);
    }

    protected function callConnectionFactoryMethod($method, ...$arguments)
    {
        return (new ReflectionMethod(ConnectionFactory::class, $method))->invoke(
            new ConnectionFactory(m::mock(Container::class)),
            ...$arguments
        );
    }
}
