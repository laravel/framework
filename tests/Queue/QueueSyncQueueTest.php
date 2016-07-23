<?php

use Mockery as m;

class QueueSyncQueueTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushShouldFireJobInstantly()
    {
        unset($_SERVER['__sync.test']);

        /*
         * Test Synced Closure
         */
        $sync = new Illuminate\Queue\SyncQueue;
        $container = new Illuminate\Container\Container;
        $encrypter = new Illuminate\Encryption\Encrypter(str_repeat('b', 16));
        $container->instance('Illuminate\Contracts\Encryption\Encrypter', $encrypter);
        $sync->setContainer($container);
        $sync->setEncrypter($encrypter);
        $sync->push(function ($job) {
            $_SERVER['__sync.test'] = true;
            $job->delete();
        });

        $this->assertTrue($_SERVER['__sync.test']);
        unset($_SERVER['__sync.test']);

        /*
         * Test Synced Class Handler
         */
        $sync->push('SyncQueueTestHandler', ['foo' => 'bar']);
        $this->assertInstanceOf('Illuminate\Queue\Jobs\SyncJob', $_SERVER['__sync.test'][0]);
        $this->assertEquals(['foo' => 'bar'], $_SERVER['__sync.test'][1]);
    }

    public function testFailedJobGetsHandledWhenAnExceptionIsThrown()
    {
        unset($_SERVER['__sync.failed']);

        $sync = new Illuminate\Queue\SyncQueue;
        $container = new Illuminate\Container\Container;
        $encrypter = new Illuminate\Encryption\Encrypter(str_repeat('c', 16));
        $container->instance('Illuminate\Contracts\Encryption\Encrypter', $encrypter);
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher');
        $events->shouldReceive('fire')->times(3);
        $container->instance('events', $events);
        $sync->setContainer($container);
        $sync->setEncrypter($encrypter);

        try {
            $sync->push('FailingSyncQueueTestHandler', ['foo' => 'bar']);
        } catch (Exception $e) {
            $this->assertTrue($_SERVER['__sync.failed']);
        }
    }
}

class SyncQueueTestEntity implements Illuminate\Contracts\Queue\QueueableEntity
{
    public function getQueueableId()
    {
        return 1;
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
        throw new Exception();
    }

    public function failed()
    {
        $_SERVER['__sync.failed'] = true;
    }
}
