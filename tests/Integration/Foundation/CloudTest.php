<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Foundation\Cloud;
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

    public function test_it_respects_log_levels()
    {
        if (isset($_SERVER['LOG_LEVEL'])) {
            $logLevelBackup = $_SERVER['LOG_LEVEL'];
        }

        $_SERVER['LOG_LEVEL'] = 'notice';

        Cloud::configureCloudLogging($this->app);

        $this->assertEquals('notice', $this->app['config']->get('logging.channels.laravel-cloud-socket.level'));

        unset($_SERVER['LOG_LEVEL']);

        if (isset($logLevelBackup)) {
            $_SERVER['LOG_LEVEL'] = $logLevelBackup;
        }
    }
}
