<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Event;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class CallQueuedHandlerTest extends TestCase
{
    public function testJobCanBeDispatched()
    {
        CallQueuedHandlerTestJob::$handled = false;

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);
        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isDeleted')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize(new CallQueuedHandlerTestJob),
        ]);

        $this->assertTrue(CallQueuedHandlerTestJob::$handled);
    }

    public function testJobCanBeDispatchedThroughMiddleware()
    {
        CallQueuedHandlerTestJobWithMiddleware::$handled = false;
        CallQueuedHandlerTestJobWithMiddleware::$middlewareCommand = null;

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);
        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isDeleted')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize($command = new CallQueuedHandlerTestJobWithMiddleware),
        ]);

        $this->assertInstanceOf(CallQueuedHandlerTestJobWithMiddleware::class, CallQueuedHandlerTestJobWithMiddleware::$middlewareCommand);
        $this->assertTrue(CallQueuedHandlerTestJobWithMiddleware::$handled);
    }

    public function testJobCanBeDispatchedThroughMiddlewareOnDispatch()
    {
        $_SERVER['__test.dispatchMiddleware'] = false;
        CallQueuedHandlerTestJobWithMiddleware::$handled = false;
        CallQueuedHandlerTestJobWithMiddleware::$middlewareCommand = null;

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);
        $job->shouldReceive('hasFailed')->andReturn(false);
        $job->shouldReceive('isDeleted')->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->andReturn(false);
        $job->shouldReceive('delete')->once();

        $command = $command = new CallQueuedHandlerTestJobWithMiddleware;
        $command->through([new TestJobMiddleware]);

        $instance->call($job, [
            'command' => serialize($command),
        ]);

        $this->assertInstanceOf(CallQueuedHandlerTestJobWithMiddleware::class, CallQueuedHandlerTestJobWithMiddleware::$middlewareCommand);
        $this->assertTrue(CallQueuedHandlerTestJobWithMiddleware::$handled);
        $this->assertTrue($_SERVER['__test.dispatchMiddleware']);
    }

    public function testJobIsMarkedAsFailedIfModelNotFoundExceptionIsThrown()
    {
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);
        $job->shouldReceive('resolveName')->andReturn(__CLASS__);
        $job->shouldReceive('fail')->once();

        $instance->call($job, [
            'command' => serialize(new CallQueuedHandlerExceptionThrower),
        ]);
    }

    public function testJobIsDeletedIfHasDeleteProperty()
    {
        Event::fake();

        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);
        $job->shouldReceive('getConnectionName')->andReturn('connection');
        $job->shouldReceive('resolveName')->andReturn(CallQueuedHandlerExceptionThrower::class);
        $job->shouldReceive('markAsFailed')->never();
        $job->shouldReceive('isDeleted')->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('failed')->never();

        $instance->call($job, [
            'command' => serialize(new CallQueuedHandlerExceptionThrower),
        ]);

        Event::assertNotDispatched(JobFailed::class);
    }
}

class CallQueuedHandlerTestJob
{
    use InteractsWithQueue;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }
}

/** This exists to test that middleware can also be defined in base classes */
abstract class AbstractCallQueuedHandlerTestJobWithMiddleware
{
    public static $middlewareCommand;

    public function middleware()
    {
        return [
            new class
            {
                public function handle($command, $next)
                {
                    AbstractCallQueuedHandlerTestJobWithMiddleware::$middlewareCommand = $command;

                    return $next($command);
                }
            },
        ];
    }
}

class CallQueuedHandlerTestJobWithMiddleware extends AbstractCallQueuedHandlerTestJobWithMiddleware
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }
}

class CallQueuedHandlerExceptionThrower
{
    public $deleteWhenMissingModels = true;

    public function handle()
    {
        //
    }

    public function __wakeup()
    {
        throw new ModelNotFoundException('Foo');
    }
}

class TestJobMiddleware
{
    public function handle($command, $next)
    {
        $_SERVER['__test.dispatchMiddleware'] = true;

        return $next($command);
    }
}
