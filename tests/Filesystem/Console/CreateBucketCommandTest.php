<?php

namespace Illuminate\Tests\Filesystem\Console;

use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Result;
use Closure;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\RequestInterface;

class CreateBucketCommandTest extends TestCase
{
    public function testItCreatesTheConfiguredBucket(): void
    {
        $this->fakeS3Disk('s3', [
            'bucket' => 'configured-bucket',
            'region' => 'us-east-1',
        ], function (CommandInterface $command) {
            $this->assertSame('configured-bucket', $command['Bucket']);
            $this->assertArrayNotHasKey('CreateBucketConfiguration', $command->toArray());

            return new Result();
        });

        $this->artisan('storage:create-bucket')
            ->expectsOutputToContain('Bucket [configured-bucket] created successfully.')
            ->assertSuccessful();
    }

    public function testItCanCreateAnExplicitBucketUsingAnotherDisk(): void
    {
        $this->fakeS3Disk('minio', [
            'bucket' => 'configured-bucket',
            'endpoint' => 'http://minio.test:9000',
            'region' => 'us-east-1',
            'use_path_style_endpoint' => true,
        ], function (CommandInterface $command, RequestInterface $request) {
            $this->assertSame('explicit-bucket', $command['Bucket']);
            $this->assertArrayNotHasKey('CreateBucketConfiguration', $command->toArray());
            $this->assertSame('minio.test', $request->getUri()->getHost());
            $this->assertSame(9000, $request->getUri()->getPort());
            $this->assertSame('/explicit-bucket', $request->getUri()->getPath());

            return new Result();
        });

        $this->artisan('storage:create-bucket', [
            'name' => 'explicit-bucket',
            '--disk' => 'minio',
        ])->assertSuccessful();
    }

    public function testItUsesTheConfiguredAwsRegionAsTheLocationConstraint(): void
    {
        $this->fakeS3Disk('s3', [
            'bucket' => 'regional-bucket',
            'region' => 'eu-west-1',
        ], function (CommandInterface $command) {
            $this->assertSame([
                'LocationConstraint' => 'eu-west-1',
            ], $command['CreateBucketConfiguration']);

            return new Result();
        });

        $this->artisan('storage:create-bucket')->assertSuccessful();
    }

    public function testItFailsWhenTheDiskDoesNotUseTheS3Driver(): void
    {
        $this->app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => __DIR__,
        ]);

        $this->artisan('storage:create-bucket', ['--disk' => 'local'])
            ->expectsOutputToContain('The [local] disk does not use the S3 driver.')
            ->assertFailed();
    }

    public function testItFailsWhenNoBucketNameIsAvailable(): void
    {
        $this->app['config']->set('filesystems.disks.s3', [
            'driver' => 's3',
        ]);

        $this->artisan('storage:create-bucket')
            ->expectsOutputToContain('The [s3] disk does not have a configured bucket.')
            ->assertFailed();
    }

    protected function fakeS3Disk(string $name, array $config, Closure $handler): void
    {
        $mock = new MockHandler();
        $mock->append($handler);

        $this->app['config']->set("filesystems.disks.{$name}", array_merge([
            'driver' => 's3',
            'credentials' => false,
            'handler' => $mock,
        ], $config));
    }
}
