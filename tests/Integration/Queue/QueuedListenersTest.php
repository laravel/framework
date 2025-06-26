<?php

namespace Illuminate\Tests\Integration\Queue;

use Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Queue\OnConnection;
use Illuminate\Foundation\Queue\OnQueue;
use Illuminate\Queue\Events\JobProcessing;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Queue;

class QueuedListenersTest extends TestCase
{
    public function testListenersCanBeQueuedOptionally()
    {
        Queue::fake();

        Event::listen(QueuedListenersTestEvent::class, QueuedListenersTestListenerShouldQueue::class);
        Event::listen(QueuedListenersTestEvent::class, QueuedListenersTestListenerShouldNotQueue::class);

        Event::dispatch(
            new QueuedListenersTestEvent
        );

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class == QueuedListenersTestListenerShouldQueue::class;
        });

        Queue::assertNotPushed(CallQueuedListener::class, function ($job) {
            return $job->class == QueuedListenersTestListenerShouldNotQueue::class;
        });
    }

    #[DataProvider('queueAndConnectionFromAttributesDataProvider')]
    public function testListenerCanSetQueueAndConnectionFromAttributes(
        string $className,
        string $connectionName,
        string $queueName
    ) {
        config(["queue.connections.{$connectionName}" => ['driver' => 'sync']]);

        Event::listen(QueuedListenersTestEvent::class, $className);

        Queue::before(function (JobProcessing $jobProcessing) use ($connectionName, $queueName) {
            $this->assertSame($connectionName, $jobProcessing->connectionName);
            $actualQueue = (new \ReflectionProperty($jobProcessing->job::class, 'queue'))->getValue($jobProcessing->job);
            $this->assertSame($queueName, $actualQueue);
        });

        Event::dispatch(new QueuedListenersTestEvent);
    }

    /**
     * @return array<string, array{class-string, string, string}>
     */
    public static function queueAndConnectionFromAttributesDataProvider(): array
    {
        return [
            'connection and queue are string attributes' => [QueuedListenerWithStringAttributes::class, 'connection-string', 'queue-string'],
            'connection and queue are enum attributes' => [QueuedListenerWithEnumAttributes::class, 'connection_name_from_enum', 'queue_name_from_enum'],
            'connection and queue are from via methods' => [QueuedListenerWithOnQueueAndOnConnectionMethods::class, 'connection-from-method', 'queue-from-method'],
            'connection and queue are from properties' => [QueuedListenerWithQueueAndConnectionProperties::class, 'connection-from-property', 'queue-from-property'],
        ];
    }
}

class QueuedListenersTestEvent
{
    //
}

class QueuedListenersTestListenerShouldQueue implements ShouldQueue
{
    public function shouldQueue()
    {
        return true;
    }
}

class QueuedListenersTestListenerShouldNotQueue implements ShouldQueue
{
    public function shouldQueue()
    {
        return false;
    }
}

#[OnConnection('connection-string')]
#[OnQueue('queue-string')]
class QueuedListenerWithStringAttributes implements ShouldQueue
{
    public function __invoke()
    {
    }
}

#[OnConnection(QueuedListenersTestEnum::connection_name_from_enum)]
#[OnQueue(QueuedListenersTestEnum::queue_name_from_enum)]
class QueuedListenerWithEnumAttributes implements ShouldQueue
{
    public function __invoke()
    {
    }
}

#[OnConnection(QueuedListenersTestEnum::connection_name_from_enum)]
#[OnQueue('should-not-see-this')]
class QueuedListenerWithOnQueueAndOnConnectionMethods implements ShouldQueue
{
    public function __invoke()
    {
    }

    public function viaQueue(): string
    {
        return 'queue-from-method';
    }

    public function viaConnection(): string
    {
        return 'connection-from-method';
    }
}

#[OnConnection('should-not-see-this')]
#[OnQueue(QueuedListenersTestEnum::queue_name_from_enum)]
class QueuedListenerWithQueueAndConnectionProperties implements ShouldQueue
{
    public $queue = 'queue-from-property';
    public $connection = 'connection-from-property';

    public function __invoke()
    {
    }
}

enum QueuedListenersTestEnum
{
    case queue_name_from_enum;
    case connection_name_from_enum;
}
