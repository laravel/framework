<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use RuntimeException;

#[RequiresPhpExtension('redis')]
class ThrottlesExceptionsWithRedisTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();

        Carbon::setTestNow(now());
    }

    protected function tearDown(): void
    {
        $this->tearDownRedis();

        parent::tearDown();
    }

    public function testCircuitIsOpenedForJobErrors()
    {
        $this->assertJobWasReleasedImmediately(CircuitBreakerWithRedisTestJob::class, $key = Str::random());
        $this->assertJobWasReleasedImmediately(CircuitBreakerWithRedisTestJob::class, $key);
        $this->assertJobWasReleasedWithDelay(CircuitBreakerWithRedisTestJob::class, $key);
    }

    public function testCircuitStaysClosedForSuccessfulJobs()
    {
        $this->assertJobRanSuccessfully(CircuitBreakerWithRedisSuccessfulJob::class, $key = Str::random());
        $this->assertJobRanSuccessfully(CircuitBreakerWithRedisSuccessfulJob::class, $key);
        $this->assertJobRanSuccessfully(CircuitBreakerWithRedisSuccessfulJob::class, $key);
    }

    public function testCircuitResetsAfterSuccess()
    {
        $this->assertJobWasReleasedImmediately(CircuitBreakerWithRedisTestJob::class, $key = Str::random());
        $this->assertJobRanSuccessfully(CircuitBreakerWithRedisSuccessfulJob::class, $key);
        $this->assertJobWasReleasedImmediately(CircuitBreakerWithRedisTestJob::class, $key);
        $this->assertJobWasReleasedImmediately(CircuitBreakerWithRedisTestJob::class, $key);
        $this->assertJobWasReleasedWithDelay(CircuitBreakerWithRedisTestJob::class, $key);
    }

    protected function assertJobWasReleasedImmediately($class, $key)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('release')->with(0)->once();
        $job->shouldReceive('isReleased')->andReturn(true);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(true);

        $instance->call($job, [
            'command' => serialize($command = new $class($key)),
        ]);

        $this->assertTrue($class::$handled);
    }

    protected function assertJobWasReleasedWithDelay($class, $key)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('release')->withArgs(function ($delay) {
            return $delay >= 600;
        })->once();
        $job->shouldReceive('isReleased')->andReturn(true);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(true);

        $instance->call($job, [
            'command' => serialize($command = new $class($key)),
        ]);

        $this->assertFalse($class::$handled);
    }

    protected function assertJobRanSuccessfully($class, $key)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();

        $instance->call($job, [
            'command' => serialize($command = new $class($key)),
        ]);

        $this->assertTrue($class::$handled);
    }

    public function testReportingExceptions()
    {
        $this->spy(ExceptionHandler::class)
            ->shouldReceive('report')
            ->twice()
            ->with(m::type(RuntimeException::class));

        $job = new class
        {
            public function release()
            {
                return $this;
            }
        };
        $next = function () {
            throw new RuntimeException('Whoops!');
        };

        $middleware = new ThrottlesExceptionsWithRedis();

        $middleware->report();
        $middleware->handle($job, $next);

        $middleware->report(fn () => true);
        $middleware->handle($job, $next);

        $middleware->report(fn () => false);
        $middleware->handle($job, $next);
    }
}

class CircuitBreakerWithRedisTestJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }

    public function middleware()
    {
        return [(new ThrottlesExceptionsWithRedis(2, 10 * 60))->by($this->key)];
    }
}

class CircuitBreakerWithRedisSuccessfulJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [(new ThrottlesExceptionsWithRedis(2, 10 * 60))->by($this->key)];
    }
}
