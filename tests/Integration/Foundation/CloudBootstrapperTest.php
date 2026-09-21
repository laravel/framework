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

        unset($_SERVER['LARAVEL_CLOUD_DISK_CONFIG']);

        [$_ENV['FILESYSTEM_DISK'], $_SERVER['FILESYSTEM_DISK'], $putenvDisk] = $filesystemDisk;

        $putenvDisk === false ? putenv('FILESYSTEM_DISK') : putenv('FILESYSTEM_DISK='.$putenvDisk);
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
