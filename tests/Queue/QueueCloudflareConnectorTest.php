<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Http\Client\Factory;
use Illuminate\Queue\CloudflareQueue;
use Illuminate\Queue\Connectors\CloudflareConnector;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class QueueCloudflareConnectorTest extends TestCase
{
    public function testMissingAccountIdThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('account_id');

        (new CloudflareConnector(new Factory))->connect([
            'queue_id' => 'queue-id',
            'token' => 'token',
        ]);
    }

    public function testMissingQueueIdThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('queue_id');

        (new CloudflareConnector(new Factory))->connect([
            'account_id' => 'account-id',
            'token' => 'token',
        ]);
    }

    public function testMissingTokenThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('token');

        (new CloudflareConnector(new Factory))->connect([
            'account_id' => 'account-id',
            'queue_id' => 'queue-id',
        ]);
    }

    public function testConnectReturnsCloudflareQueueInstance()
    {
        $queue = (new CloudflareConnector(new Factory))->connect([
            'account_id' => 'account-id',
            'queue_id' => 'queue-id',
            'api_token' => 'token',
            'queue' => 'emails',
            'batch_size' => 5,
            'visibility_timeout_ms' => 60_000,
        ]);

        $this->assertInstanceOf(CloudflareQueue::class, $queue);
    }
}
