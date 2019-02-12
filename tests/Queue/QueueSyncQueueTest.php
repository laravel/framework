<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Mockery as m;
use Illuminate\Queue\SyncQueue;
use PHPUnit\Framework\TestCase;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\QueueableEntity;

class QueueSyncQueueTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testPushShouldFireJobInstantly()
    {
        unset($_SERVER['__sync.test']);

        $sync = new SyncQueue;
        $container = new Container;
        $sync->setContainer($container);

        $sync->push(SyncQueueTestHandler::class, ['foo' => 'bar']);
        $this->assertInstanceOf(SyncJob::class, $_SERVER['__sync.test'][0]);
        $this->assertEquals(['foo' => 'bar'], $_SERVER['__sync.test'][1]);
    }

    public function testFailedJobGetsHandledWhenAnExceptionIsThrown()
    {
        unset($_SERVER['__sync.failed']);

        $sync = new SyncQueue;
        $container = new Container;
        Container::setInstance($container);
        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->times(3);
        $container->instance('events', $events);
        $container->instance(Dispatcher::class, $events);
        $sync->setContainer($container);

        try {
            $sync->push(FailingSyncQueueTestHandler::class, ['foo' => 'bar']);
        } catch (Exception $e) {
            $this->assertTrue($_SERVER['__sync.failed']);
        }

        Container::setInstance();
    }
}

class SyncQueueTestEntity implements QueueableEntity
{
    public function getQueueableId()
    {
        return 1;
    }

    public function getQueueableConnection()
    {
        //
    }

    public function getQueueableRelations()
    {
        //
    }
}

class SyncQueueTestHandler
{
    public function fire($job, $data)
    {
        $_SERVER['__sync.test'] = func_get_args();
    }
}

class FailingSyncQueueTestHandler
{
    public function fire($job, $data)
    {
        throw new Exception;
    }

    public function failed()
    {
        $_SERVER['__sync.failed'] = true;
    }
}
