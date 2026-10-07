<?php

namespace Illuminate\Tests\Foundation\Cloud;

use JMac\Testing\Double;
use Illuminate\Foundation\Cloud\CloudManager;
use Illuminate\Foundation\Cloud\Queue as CloudQueue;
use Illuminate\Support\Facades\Cloud;
use Mockery;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;

class CloudManagerTest extends TestCase
{
    #[TestWith([null, false])]
    #[TestWith(['sqs', false])]
    #[TestWith(['cloud', true])]
    public function testUsesManagedQueuesReflectsTheCloudConnectionDriver(?string $driver, bool $managed)
    {
        config(['queue.connections.cloud.driver' => $driver]);

        $this->assertSame($managed, Cloud::usesManagedQueues());
    }

    public function testQueueThrowsWhenManagedQueuesAreNotConfigured()
    {
        $this->expectExceptionObject(new RuntimeException(
            'Laravel Cloud managed queues are not configured for this application.'
        ));

        Cloud::queue();
    }

    #[TestWith(['emails', true])]
    #[TestWith(['exports', false])]
    public function testIsManagedQueueChecksTheConfiguredManagedQueues(string $queue, bool $managed)
    {
        $cloudQueue = Double::for(CloudQueue::class);
        $cloudQueue->allows('managedQueues')->returns(['emails']);

        $cloud = Cloud::partialMock();
        $cloud->allows('usesManagedQueues')->returns(true);
        $cloud->allows('queue')->returns($cloudQueue);

        $this->assertSame($managed, Cloud::isManagedQueue($queue));
    }

    public function testFacadeResolvesTheCloudManager()
    {
        $this->assertInstanceOf(CloudManager::class, Cloud::getFacadeRoot());
    }

    public function testCloudManagerIsMacroable()
    {
        CloudManager::macro('foo', fn () => 'bar');

        $this->assertSame('bar', Cloud::foo());
    }
}
