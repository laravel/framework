<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Database\Connectors\ConnectorInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\CloudBootstrapper;
use Mockery;
use Orchestra\Testbench\Attributes\WithEnv;
use Orchestra\Testbench\TestCase;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

class CloudBootstrapperTest extends TestCase
{
    public function test_it_configures_a_read_replica_connection_after_configuration_is_loaded()
    {
        $_SERVER['DB_READ_HOST'] = 'read.example.com';
        $this->app['config']->set('database.default', 'mysql');
        $this->app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => 'write.example.com',
        ]);

        CloudBootstrapper::bootstrapped($this->app, LoadConfiguration::class);

        $this->assertSame('read.example.com', $this->app['config']->get('database.connections.mysql.read.host'));
        $this->assertSame('write.example.com', $this->app['config']->get('database.connections.mysql.host'));

        unset($_SERVER['DB_READ_HOST']);
    }

    public function test_it_does_not_configure_a_read_replica_connection_without_a_read_host()
    {
        unset($_SERVER['DB_READ_HOST']);

        $connection = [
            'driver' => 'mysql',
            'host' => 'write.example.com',
        ];

        $this->app['config']->set('database.default', 'mysql');
        $this->app['config']->set('database.connections.mysql', $connection);

        CloudBootstrapper::configureReadReplicaConnection($this->app);

        $this->assertSame($connection, $this->app['config']->get('database.connections.mysql'));
    }

    #[WithEnv('DB_POOLING', null)]
    #[DataProvider('legacyPostgresHosts')]
    public function test_it_retains_legacy_behavior_without_the_environment_variable($host, $migrationConnection)
    {
        $config = ['driver' => 'pgsql', 'host' => $host, 'database' => 'test'];
        $this->app['config']->set('database.connections', [
            'pgsql' => $config,
            'reporting' => $config,
        ]);
        $callback = new ReflectionProperty(Migrator::class, 'connectionResolverCallback');
        $previous = $callback->getValue();

        try {
            CloudBootstrapper::bootstrapped($this->app, LoadConfiguration::class);

            $this->assertSame($host, $this->app['config']->get('database.connections.pgsql.host'));
            $this->assertSame($config, $this->app['config']->get('database.connections.reporting'));
            $this->assertNull($this->app['config']->get('database.connections.pgsql.direct'));
            $this->assertSame($migrationConnection, $this->app['migrator']->resolveConnection('pgsql')->getName());

            if ($migrationConnection === 'pgsql-unpooled') {
                $this->assertSame('test.pg.laravel.cloud', $this->app['config']->get('database.connections.pgsql-unpooled.host'));
            } else {
                $this->assertSame($config, $this->app['config']->get('database.connections.pgsql'));
                $this->assertNull($this->app['config']->get('database.connections.pgsql-unpooled'));
            }
        } finally {
            $callback->setValue(null, $previous);
        }
    }

    public static function legacyPostgresHosts()
    {
        return [
            ['test.pg.laravel.cloud', 'pgsql'],
            ['test-pooler.pg.laravel.cloud', 'pgsql-unpooled'],
        ];
    }

    #[WithEnv('DB_POOLING', null)]
    public function test_legacy_pooler_configuration_preserves_existing_pdo_options()
    {
        $options = [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_PERSISTENT => true];
        $this->app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => 'test-pooler.pg.laravel.cloud',
            'options' => $options,
        ]);

        CloudBootstrapper::configureUnpooledPostgresConnection($this->app);

        $this->assertSame(
            [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_PERSISTENT => true, PDO::ATTR_EMULATE_PREPARES => true],
            $this->app['config']->get('database.connections.pgsql.options')
        );
        $this->assertSame($options, $this->app['config']->get('database.connections.pgsql-unpooled.options'));
    }

    #[DataProvider('postgresHosts')]
    #[WithEnv('DB_POOLING', 'true')]
    public function test_it_configures_native_pooled_connections_and_a_legacy_direct_connection($host, $pooledHost, $directHost)
    {
        $this->app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql', 'host' => $host, 'database' => 'test',
            'username' => 'test-username', 'password' => 'test-password',
            'options' => [PDO::ATTR_TIMEOUT => 5],
        ]);

        CloudBootstrapper::bootstrapped($this->app, LoadConfiguration::class);

        $connection = $this->app['db']->connection('pgsql');
        $direct = $connection->getDirectPdoConfig();
        $legacy = $this->app['db']->connection('pgsql-unpooled');

        $this->assertSame($pooledHost, $connection->getConfig('host'));
        $this->assertTrue($connection->getConfig('pooled'));
        $this->assertSame([PDO::ATTR_TIMEOUT => 5, PDO::ATTR_EMULATE_PREPARES => true], $connection->getConfig('options'));
        $this->assertSame($directHost, $direct['host']);
        $this->assertSame('test-password', $direct['password']);
        $this->assertFalse($direct['options'][PDO::ATTR_EMULATE_PREPARES]);
        $this->assertSame($directHost, $legacy->getConfig('host'));
        $this->assertSame($direct['options'], $legacy->getConfig('options'));
        $this->assertFalse($legacy->hasDirectConnection());

        $config = $this->app['config']->get('database.connections');
        CloudBootstrapper::configurePostgresConnections($this->app);
        $this->assertSame($config, $this->app['config']->get('database.connections'));
    }

    public static function postgresHosts()
    {
        return [
            'direct' => ['test.pg.laravel.cloud', 'test-pooler.pg.laravel.cloud', 'test.pg.laravel.cloud'],
            'pooled' => ['test-pooler.pg.laravel.cloud', 'test-pooler.pg.laravel.cloud', 'test.pg.laravel.cloud'],
            'embedded suffix' => ['test-pooler-name-pooler.pg.laravel.cloud', 'test-pooler-name-pooler.pg.laravel.cloud', 'test-pooler-name.pg.laravel.cloud'],
        ];
    }

    #[WithEnv('DB_POOLING', 'true')]
    public function test_it_resolves_database_urls_before_configuring_pooling()
    {
        $this->app['config']->set('database.connections', [
            'pgsql' => [
                'driver' => 'pgsql', 'host' => 'ignored.example.com',
                'url' => 'postgres://user:p%40ss@test-pooler.pg.laravel.cloud:5432/database?sslmode=require',
            ],
            'reporting' => 'postgresql://user:password@reporting.pg.laravel.cloud/database',
            'external' => [
                'driver' => 'pgsql', 'host' => 'ignored.pg.laravel.cloud',
                'url' => 'postgres://external.example.com/database',
            ],
        ]);
        $external = $this->app['config']->get('database.connections.external');

        CloudBootstrapper::configurePostgresConnections($this->app);

        $connection = $this->app['db']->connection('pgsql');
        $this->assertSame('test-pooler.pg.laravel.cloud', $connection->getConfig('host'));
        $this->assertSame('p@ss', $connection->getConfig('password'));
        $this->assertSame('require', $connection->getDirectPdoConfig()['sslmode']);
        $this->assertSame('test.pg.laravel.cloud', $this->app['db']->connection('pgsql-unpooled')->getConfig('host'));
        $this->assertSame('reporting-pooler.pg.laravel.cloud', $this->app['db']->connection('reporting')->getConfig('host'));
        $this->assertSame($external, $this->app['config']->get('database.connections.external'));
    }

    #[WithEnv('DB_POOLING', 'false')]
    public function test_opt_out_forces_direct_hosts_and_preserves_direct_credentials()
    {
        $this->app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql', 'database' => 'test',
            'url' => 'postgres://pooled-user:password@test-pooler.pg.laravel.cloud/test',
            'pooled' => true,
            'options' => [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_EMULATE_PREPARES => true],
            'direct' => ['username' => 'direct-user', 'sslmode' => 'verify-full'],
        ]);

        CloudBootstrapper::configurePostgresConnections($this->app);

        $config = $this->app['config']->get('database.connections.pgsql');
        $connection = $this->app['db']->connection('pgsql');
        $this->assertSame('test.pg.laravel.cloud', $connection->getConfig('host'));
        $this->assertSame('direct-user', $connection->getConfig('username'));
        $this->assertSame('verify-full', $connection->getConfig('sslmode'));
        $this->assertFalse($connection->getConfig('pooled'));
        $this->assertFalse($connection->hasDirectConnection());
        $this->assertSame([PDO::ATTR_TIMEOUT => 5, PDO::ATTR_EMULATE_PREPARES => false], $connection->getConfig('options'));
        $this->assertArrayNotHasKey('url', $config);
        $this->assertArrayNotHasKey('direct', $config);

        CloudBootstrapper::configurePostgresConnections($this->app);
        $this->assertSame($config, $this->app['config']->get('database.connections.pgsql'));
    }

    #[WithEnv('DB_POOLING', 'true')]
    public function test_it_preserves_explicit_direct_configuration_and_existing_legacy_connections()
    {
        $direct = [
            'host' => 'custom-direct.example.com', 'username' => 'direct-user',
            'options' => [PDO::ATTR_TIMEOUT => 10, PDO::ATTR_EMULATE_PREPARES => true],
        ];
        $legacy = ['driver' => 'pgsql', 'host' => 'legacy.pg.laravel.cloud'];
        $this->app['config']->set('database.connections', [
            'pgsql' => [
                'driver' => 'pgsql', 'host' => 'test.pg.laravel.cloud', 'database' => 'test',
                'direct' => $direct,
                'options' => [PDO::ATTR_EMULATE_PREPARES => false],
            ],
            'pgsql-unpooled' => $legacy,
        ]);

        CloudBootstrapper::configurePostgresConnections($this->app);

        $connection = $this->app['db']->connection('pgsql');
        $this->assertSame($direct, $this->app['config']->get('database.connections.pgsql.direct'));
        $this->assertSame($direct['host'], $connection->getDirectPdoConfig()['host']);
        $this->assertSame($direct['options'], $connection->getDirectPdoConfig()['options']);
        $this->assertFalse($connection->getConfig('options')[PDO::ATTR_EMULATE_PREPARES]);
        $this->assertSame($legacy, $this->app['config']->get('database.connections.pgsql-unpooled'));
    }

    #[WithEnv('DB_POOLING', 'true')]
    public function test_it_leaves_unrelated_connections_unchanged()
    {
        $connections = [
            'mysql' => ['driver' => 'mysql', 'host' => 'test.pg.laravel.cloud'],
            'external' => ['driver' => 'pgsql', 'host' => 'test-pooler.neon.tech'],
            'lookalike' => ['driver' => 'pgsql', 'host' => 'test-pooler.pg.laravel.cloud.example.com'],
            'suffix' => ['driver' => 'pgsql', 'host' => 'test-pooler.notpg.laravel.cloud'],
            'invalid-host' => ['driver' => 'pgsql', 'host' => false],
            'missing-host' => ['driver' => 'pgsql'],
        ];
        $this->app['config']->set('database.connections', $connections);

        CloudBootstrapper::configurePostgresConnections($this->app);

        $this->assertSame($connections, $this->app['config']->get('database.connections'));
    }

    #[WithEnv('DB_POOLING', 'true')]
    public function test_cloud_migrations_use_native_direct_connections_for_each_connection_name()
    {
        foreach (['pgsql', 'reporting'] as $name) {
            $this->app['config']->set("database.connections.{$name}", [
                'driver' => 'pgsql', 'host' => "{$name}.pg.laravel.cloud", 'database' => 'test',
            ]);
        }
        $this->app['config']->set('database.default', 'pgsql');
        $connector = Mockery::mock(ConnectorInterface::class);
        $this->app->instance('db.connector.pgsql', $connector);

        CloudBootstrapper::bootstrapped($this->app, LoadConfiguration::class);

        foreach (['pgsql', 'reporting'] as $name) {
            $pdo = new PDO('sqlite::memory:');
            $connector->shouldReceive('connect')->once()->with(Mockery::on(fn ($config) => $config['host'] === "{$name}.pg.laravel.cloud" &&
                $config['options'][PDO::ATTR_EMULATE_PREPARES] === false
            ))->andReturn($pdo);

            $connection = $this->app['migrator']->resolveConnection($name);
            $this->assertSame("{$name}::direct", $connection->getNameWithReadWriteType());
            $this->assertSame($pdo, $connection->getPdo());
        }
    }

    public function test_it_can_configure_disks()
    {
        $filesystemDisk = [$_ENV['FILESYSTEM_DISK'] ?? null, $_SERVER['FILESYSTEM_DISK'] ?? null, getenv('FILESYSTEM_DISK')];

        unset($_ENV['FILESYSTEM_DISK'], $_SERVER['FILESYSTEM_DISK']);
        putenv('FILESYSTEM_DISK');

        $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'] = json_encode(
            [
                [
                    'disk' => 'test-disk',
                    'access_key_id' => 'test-access-key-id',
                    'access_key_secret' => 'test-access-key-secret',
                    'bucket' => 'test-bucket',
                    'url' => 'test-url',
                    'endpoint' => 'test-endpoint',
                    'is_default' => false,
                ],
                [
                    'disk' => 'test-disk-2',
                    'access_key_id' => 'test-access-key-id-2',
                    'access_key_secret' => 'test-access-key-secret-2',
                    'bucket' => 'test-bucket-2',
                    'url' => 'test-url-2',
                    'endpoint' => 'test-endpoint-2',
                    'is_default' => true,
                ],
            ]
        );

        CloudBootstrapper::configureDisks($this->app);

        $this->assertSame('test-disk-2', $this->app['config']->get('filesystems.default'));
        $this->assertSame('test-access-key-id', $this->app['config']->get('filesystems.disks.test-disk.key'));
        $this->assertSame('auto', $this->app['config']->get('filesystems.disks.test-disk.region'));

        unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG']);

        [$_ENV['FILESYSTEM_DISK'], $_SERVER['FILESYSTEM_DISK'], $putenvDisk] = $filesystemDisk;

        $putenvDisk === false ? putenv('FILESYSTEM_DISK') : putenv('FILESYSTEM_DISK='.$putenvDisk);
    }

    public function test_it_configures_disks_with_cacheable_credential_providers()
    {
        $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'] = json_encode([
            [
                'disk' => 'aws-bucket',
                'access_key_id' => null,
                'access_key_secret' => null,
                'bucket' => 'arn:aws:s3:us-east-2:123456789012:accesspoint/environment-bucket',
                'url' => null,
                'endpoint' => 'https://s3-accesspoint.us-east-2.amazonaws.com',
                'region' => 'us-east-2',
                'credentials' => 'ecs',
            ],
        ]);

        try {
            CloudBootstrapper::configureDisks($this->app);

            $config = $this->app['config']->get('filesystems.disks.aws-bucket');

            $this->assertSame('us-east-2', $config['region']);
            $this->assertSame('ecs', $config['credentials']);
            $this->assertSame('https://s3-accesspoint.us-east-2.amazonaws.com', $config['endpoint']);
            $this->assertArrayNotHasKey('ignore_configured_endpoint_urls', $config);
            $this->assertArrayNotHasKey('auth_mode', $config);
            $this->assertNull($config['key']);
            $this->assertNull($config['secret']);
            $this->assertSame($config, eval('return '.var_export($config, true).';'));
        } finally {
            unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG']);
        }
    }

    public function test_it_does_not_override_a_different_filesystem_disk()
    {
        $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'] = json_encode(
            [
                [
                    'disk' => 'test-disk',
                    'access_key_id' => 'test-access-key-id',
                    'access_key_secret' => 'test-access-key-secret',
                    'bucket' => 'test-bucket',
                    'url' => 'test-url',
                    'endpoint' => 'test-endpoint',
                    'is_default' => true,
                ],
            ]
        );
        $_SERVER['FILESYSTEM_DISK'] = 'read-through';
        $this->app['config']->set('filesystems.default', 'read-through');

        CloudBootstrapper::configureDisks($this->app);

        $this->assertSame('read-through', $this->app['config']->get('filesystems.default'));
        $this->assertSame('test-access-key-id', $this->app['config']->get('filesystems.disks.test-disk.key'));

        unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG'], $_SERVER['FILESYSTEM_DISK']);
    }

    public function test_it_does_not_override_a_different_filesystem_disk_defined_only_in_the_env_superglobal()
    {
        $filesystemDisk = [$_SERVER['FILESYSTEM_DISK'] ?? null, getenv('FILESYSTEM_DISK')];

        unset($_SERVER['FILESYSTEM_DISK']);
        putenv('FILESYSTEM_DISK');

        $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'] = json_encode(
            [
                [
                    'disk' => 'test-disk',
                    'access_key_id' => 'test-access-key-id',
                    'access_key_secret' => 'test-access-key-secret',
                    'bucket' => 'test-bucket',
                    'url' => 'test-url',
                    'endpoint' => 'test-endpoint',
                    'is_default' => true,
                ],
            ]
        );
        $_ENV['FILESYSTEM_DISK'] = 'read-through';
        $this->app['config']->set('filesystems.default', 'read-through');

        CloudBootstrapper::configureDisks($this->app);

        $this->assertSame('read-through', $this->app['config']->get('filesystems.default'));
        $this->assertSame('test-access-key-id', $this->app['config']->get('filesystems.disks.test-disk.key'));

        unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG'], $_ENV['FILESYSTEM_DISK']);

        [$_SERVER['FILESYSTEM_DISK'], $putenvDisk] = $filesystemDisk;

        $putenvDisk === false ? putenv('FILESYSTEM_DISK') : putenv('FILESYSTEM_DISK='.$putenvDisk);
    }

    public function test_it_does_not_change_the_default_disk_when_no_cloud_disk_is_default()
    {
        $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'] = json_encode(
            [
                [
                    'disk' => 'test-disk',
                    'access_key_id' => 'test-access-key-id',
                    'access_key_secret' => 'test-access-key-secret',
                    'bucket' => 'test-bucket',
                    'url' => 'test-url',
                    'endpoint' => 'test-endpoint',
                ],
                [
                    'disk' => 'test-disk-2',
                    'access_key_id' => 'test-access-key-id-2',
                    'access_key_secret' => 'test-access-key-secret-2',
                    'bucket' => 'test-bucket-2',
                    'url' => 'test-url-2',
                    'endpoint' => 'test-endpoint-2',
                    'is_default' => false,
                ],
            ]
        );
        $this->app['config']->set('filesystems.default', 'local');

        CloudBootstrapper::configureDisks($this->app);

        $this->assertSame('local', $this->app['config']->get('filesystems.default'));
        $this->assertSame('test-access-key-id', $this->app['config']->get('filesystems.disks.test-disk.key'));
        $this->assertSame('test-access-key-id-2', $this->app['config']->get('filesystems.disks.test-disk-2.key'));

        unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG']);
    }

    public function test_it_uses_the_cloud_default_disk_when_the_filesystem_disk_is_blank()
    {
        $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'] = json_encode(
            [
                [
                    'disk' => 'test-disk',
                    'access_key_id' => 'test-access-key-id',
                    'access_key_secret' => 'test-access-key-secret',
                    'bucket' => 'test-bucket',
                    'url' => 'test-url',
                    'endpoint' => 'test-endpoint',
                    'is_default' => true,
                ],
            ]
        );
        $_SERVER['FILESYSTEM_DISK'] = '';
        $this->app['config']->set('filesystems.default', '');

        CloudBootstrapper::configureDisks($this->app);

        $this->assertSame('test-disk', $this->app['config']->get('filesystems.default'));

        unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG'], $_SERVER['FILESYSTEM_DISK']);
    }

    public function test_it_can_configure_scoped_disks()
    {
        $_SERVER['LARAVEL_CLOUD_DISK_CONFIG'] = json_encode(
            [
                [
                    'disk' => 'test-disk',
                    'access_key_id' => 'test-access-key-id',
                    'access_key_secret' => 'test-access-key-secret',
                    'bucket' => 'test-bucket',
                    'url' => 'test-url',
                    'endpoint' => 'test-endpoint',
                ],
                [
                    'disk' => 'test-disk-scoped',
                    'scoped_disk' => 'test-disk',
                    'prefix' => 'test/prefix/',
                    'is_default' => true,
                ],
            ]
        );

        CloudBootstrapper::configureDisks($this->app);

        $this->assertSame('scoped', $this->app['config']->get('filesystems.disks.test-disk-scoped.driver'));
        $this->assertSame('test-disk', $this->app['config']->get('filesystems.disks.test-disk-scoped.disk'));

        unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG']);
    }

    public function test_it_respects_log_levels()
    {
        if (isset($_SERVER['LOG_LEVEL'])) {
            $logLevelBackup = $_SERVER['LOG_LEVEL'];
        }

        $_SERVER['LOG_LEVEL'] = 'notice';

        CloudBootstrapper::configureCloudLogging($this->app);

        $this->assertSame('notice', $this->app['config']->get('logging.channels.laravel-cloud-socket.level'));

        unset($_SERVER['LOG_LEVEL']);

        if (isset($logLevelBackup)) {
            $_SERVER['LOG_LEVEL'] = $logLevelBackup;
        }
    }

    public function test_it_configures_a_cloud_logging_socket_timeout()
    {
        CloudBootstrapper::configureCloudLogging($this->app);

        $this->assertSame(2.0, $this->app['config']->get('logging.channels.laravel-cloud-socket.with.timeout'));
    }

    public function test_it_aliases_cloud_logging_channel()
    {
        CloudBootstrapper::configureCloudLogging($this->app);

        $this->assertSame(
            $this->app['config']->get('logging.channels.laravel-cloud-socket'),
            $this->app['config']->get('logging.channels.cloud')
        );
    }

    public function test_it_does_not_replace_existing_cloud_logging_channel()
    {
        $this->app['config']->set('logging.channels.cloud', [
            'driver' => 'single',
            'path' => 'test.log',
        ]);

        CloudBootstrapper::configureCloudLogging($this->app);

        $this->assertSame([
            'driver' => 'single',
            'path' => 'test.log',
        ], $this->app['config']->get('logging.channels.cloud'));
    }
}
