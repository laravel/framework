<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Cloud;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\SocketHandler;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

class CloudTest extends TestCase
{
    #[WithConfig('database.connections.pgsql', ['host' => 'test-pooler.pg.laravel.cloud', 'username' => 'test-username', 'password' => 'test-password'])]
    public function test_it_can_resolve_core_container_aliases()
    {
        Cloud::configureUnpooledPostgresConnection($this->app);

        $this->assertEquals([
            'host' => 'test.pg.laravel.cloud',
            'username' => 'test-username',
            'password' => 'test-password',
        ], $this->app['config']->get('database.connections.pgsql-unpooled'));
    }

    public function test_it_can_configure_disks()
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

        Cloud::configureDisks($this->app);

        $this->assertEquals('test-disk-2', $this->app['config']->get('filesystems.default'));
        $this->assertEquals('test-access-key-id', $this->app['config']->get('filesystems.disks.test-disk.key'));

        unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG']);
    }

    public function test_it_can_bootstrap_bootstraped()
    {
        Cloud::bootstrapperBootstrapped($this->app, 'random_string');
        $this->assertEquals(null, $this->app['config']->get('logging.channels.laravel-cloud-socket'));

        Cloud::bootstrapperBootstrapped($this->app, HandleExceptions::class);
        unset($_ENV['LARAVEL_CLOUD_LOG_SOCKET']);
        $this->assertEquals(['includeStacktraces' => true], $this->app['config']->get('logging.channels.stderr.formatter_with'));
        $this->assertEquals([
            'driver' => 'monolog',
            'handler' => SocketHandler::class,
            'formatter' => JsonFormatter::class,
            'formatter_with' => [
                'includeStacktraces' => true,
            ],
            'with' => [
                'connectionString' => 'unix:///tmp/cloud-init.sock',
                'persistent' => true,
            ],
        ], $this->app['config']->get('logging.channels.laravel-cloud-socket'));

        $_ENV['LARAVEL_CLOUD_LOG_SOCKET'] = 'my_random_socket 1';
        $_SERVER['LARAVEL_CLOUD_LOG_SOCKET'] = 'my_random_socket 2';

        Cloud::bootstrapperBootstrapped($this->app, HandleExceptions::class);

        $this->assertEquals([
            'driver' => 'monolog',
            'handler' => SocketHandler::class,
            'formatter' => JsonFormatter::class,
            'formatter_with' => [
                'includeStacktraces' => true,
            ],
            'with' => [
                'connectionString' => 'my_random_socket 1',
                'persistent' => true,
            ],
        ], $this->app['config']->get('logging.channels.laravel-cloud-socket'));

        unset($_ENV['LARAVEL_CLOUD_LOG_SOCKET']);
        unset($_SERVER['LARAVEL_CLOUD_LOG_SOCKET']);
    }

    public function test_it_configures_migrator_to_use_unpooled_pgsql()
    {
        $this->app['config']->set('database.connections.pgsql-unpooled', null);

        Cloud::ensureMigrationsUseUnpooledConnection($this->app);
        $callback = MyMigrator::getResolver();
        $this->assertNull($callback);

        $this->app['config']->set('database.connections.pgsql-unpooled', []);

        Cloud::ensureMigrationsUseUnpooledConnection($this->app);
        $callback = MyMigrator::getResolver();

        $spyResolver = new class
        {
            public $connection;

            public function connection($connectionName)
            {
                $this->connection = $connectionName;
            }
        };

        $callback($spyResolver, 'pgsql');
        $this->assertEquals('pgsql-unpooled', $spyResolver->connection);

        $callback($spyResolver, 'mysql');
        $this->assertEquals('mysql', $spyResolver->connection);

        MyMigrator::unsetResolver();
    }
}

class MyMigrator extends Migrator
{
    public static function getResolver()
    {
        return self::$connectionResolverCallback;
    }

    public static function unsetResolver()
    {
        self::$connectionResolverCallback = null;
    }
}
