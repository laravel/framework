<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Queue\CloudflareQueueClient;
use Illuminate\Queue\Jobs\CloudflareJob;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

class QueueCloudflareJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testDeleteAcknowledgesTheMessage()
    {
        $client = Mockery::mock(CloudflareQueueClient::class);
        $client->expects('ack')->once()->with(['lease-123']);

        $job = $this->createJob($client);

        $job->delete();
    }

    public function testReleaseRetriesTheMessageWithDelay()
    {
        $client = Mockery::mock(CloudflareQueueClient::class);
        $client->expects('retry')->once()->with([
            ['lease_id' => 'lease-123', 'delay_seconds' => 30],
        ]);

        $job = $this->createJob($client);

        $job->release(30);

        $this->assertTrue($job->isReleased());
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $client = Mockery::mock(CloudflareQueueClient::class);

        $job = $this->createJob($client, json_encode(['job' => 'foo', 'data' => ['bar'], 'attempts' => 1]));

        $handler = Mockery::mock(stdClass::class);
        $job->getContainer()->expects('make')->with('foo')->andReturn($handler);
        $handler->expects('fire')->with($job, ['bar']);

        $job->fire();
    }

    protected function createJob(CloudflareQueueClient $client, ?string $body = null): CloudflareJob
    {
        $body ??= json_encode(['job' => 'foo', 'data' => [], 'attempts' => 1]);

        $container = Mockery::mock(Container::class);

        return new CloudflareJob(
            $container,
            $client,
            [
                'id' => 'message-id',
                'lease_id' => 'lease-123',
                'attempts' => 2,
                'body' => $body,
            ],
            'cloudflare',
            'default',
        );
    }
}
