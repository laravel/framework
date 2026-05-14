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

    #[WithConfig('queue.connections.sqs', ['driver' => 'sqs', 'region' => 'us-east-1', 'queue' => 'default'])]
    public function test_it_configures_managed_queue_credentials()
    {
        $_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES'] = '1';

        try {
            Cloud::configureManagedQueues($this->app);

            $this->assertEquals('ecs', $this->app['config']->get('queue.connections.sqs.credentials'));
        } finally {
            unset($_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES']);
        }
    }

    #[WithConfig('queue.connections.sqs', ['driver' => 'sqs', 'region' => 'us-east-1', 'queue' => 'default'])]
    public function test_it_does_not_configure_managed_queues_when_not_enabled()
    {
        Cloud::configureManagedQueues($this->app);

        $this->assertNull($this->app['config']->get('queue.connections.sqs.credentials'));
    }

    #[WithConfig('queue.connections.sqs', ['driver' => 'sqs', 'region' => 'us-east-1', 'queue' => 'default'])]
    public function test_it_configures_managed_queue_region()
    {
        $_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES'] = '1';
        $_SERVER['LARAVEL_CLOUD_REGION'] = 'us-west-2';

        try {
            Cloud::configureManagedQueues($this->app);

            $this->assertEquals('us-west-2', $this->app['config']->get('queue.connections.sqs.region'));
        } finally {
            unset($_SERVER['LARAVEL_CLOUD_MANAGED_QUEUES'], $_SERVER['LARAVEL_CLOUD_REGION']);
        }
    }
}
