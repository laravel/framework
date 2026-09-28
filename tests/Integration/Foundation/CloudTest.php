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
        $this->assertSame('auto', $this->app['config']->get('filesystems.disks.test-disk.region'));

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
            Cloud::configureDisks($this->app);

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

    public function test_it_configures_a_cloud_logging_socket_timeout()
    {
        Cloud::configureCloudLogging($this->app);

        $this->assertSame(2.0, $this->app['config']->get('logging.channels.laravel-cloud-socket.with.timeout'));
    }
}
