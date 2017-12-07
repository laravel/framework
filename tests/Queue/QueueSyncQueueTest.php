<?php

namespace Illuminate\Tests\Queue;

use Exception;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;

class QueueSyncQueueTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushShouldFireJobInstantly()
    {
        unset($_SERVER['__sync.test']);

        $sync = new \Illuminate\Queue\SyncQueue;
        $container = new \Illuminate\Container\Container;
        $sync->setContainer($container);

        $sync->push('Illuminate\Tests\Queue\SyncQueueTestHandler', ['foo' => 'bar']);
        $this->assertInstanceOf('Illuminate\Queue\Jobs\SyncJob', $_SERVER['__sync.test'][0]);
        $this->assertEquals(['foo' => 'bar'], $_SERVER['__sync.test'][1]);
    }

    public function testFailedJobGetsHandledWhenAnExceptionIsThrown()
    {
        unset($_SERVER['__sync.failed']);

        $sync = new \Illuminate\Queue\SyncQueue;
        $container = new \Illuminate\Container\Container;
        Container::setInstance($container);
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('dispatch')->times(3);
        $container->instance('events', $events);
        $container->instance('Illuminate\Contracts\Events\Dispatcher', $events);
        $sync->setContainer($container);

        try {
            $sync->push('Illuminate\Tests\Queue\FailingSyncQueueTestHandler', ['foo' => 'bar']);
        } catch (Exception $e) {
            $this->assertTrue($_SERVER['__sync.failed']);
        }

        Container::setInstance();
    }
}

class SyncQueueTestEntity implements \Illuminate\Contracts\Queue\QueueableEntity
{
    public function getQueueableId()
    {
        return 1;
    }

    public function getQueueableConnection()
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
