<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Foundation\CloudBootstrapper;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

class CloudBootstrapperTest extends TestCase
{
    #[WithConfig('database.connections.pgsql', ['host' => 'test-pooler.pg.laravel.cloud', 'username' => 'test-username', 'password' => 'test-password'])]
    public function test_it_can_resolve_core_container_aliases()
    {
        CloudBootstrapper::configureUnpooledPostgresConnection($this->app);

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

        CloudBootstrapper::configureDisks($this->app);

        $this->assertSame('test-disk-2', $this->app['config']->get('filesystems.default'));
        $this->assertSame('test-access-key-id', $this->app['config']->get('filesystems.disks.test-disk.key'));

        unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG']);
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
