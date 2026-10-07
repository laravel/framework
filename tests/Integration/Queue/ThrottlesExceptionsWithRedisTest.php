<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Queue\Middleware\ThrottlesExceptionsWithRedis;
use Illuminate\Support\Str;
use Mockery;
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

        $job = new FakeJob;

        $instance->call($job, [
            'command' => serialize($command = new $class($key)),
        ]);

        $this->assertTrue($job->isReleased());
        $this->assertSame(0, $job->releaseDelay);

        $this->assertTrue($class::$handled);
    }

    protected function assertJobWasReleasedWithDelay($class, $key)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = new FakeJob;

        $instance->call($job, [
            'command' => serialize($command = new $class($key)),
        ]);

        $this->assertTrue($job->isReleased());
        $this->assertGreaterThanOrEqual(590, $job->releaseDelay);
        $this->assertLessThanOrEqual(610, $job->releaseDelay);

        $this->assertFalse($class::$handled);
    }

    protected function assertJobRanSuccessfully($class, $key)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = new FakeJob;

        $instance->call($job, [
            'command' => serialize($command = new $class($key)),
        ]);

        $this->assertTrue($job->isDeleted());

        $this->assertTrue($class::$handled);
    }

    public function testReportingExceptions()
    {
        $this->spy(ExceptionHandler::class)
            ->expects('report')
            ->times(2)
            ->with(Mockery::type(RuntimeException::class));

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

    public function testItCanBackoffUsingException()
    {
        $job = new class
        {
            public $releasedAfter;

            public function release($delay)
            {
                $this->releasedAfter = $delay;

                return $this;
            }
        };
        $expectedException = new RuntimeException('Whoops!');
        $receivedException = null;
        $next = function () use ($expectedException) {
            throw $expectedException;
        };

        $middleware = (new ThrottlesExceptionsWithRedis())->backoff(function ($throwable) use (&$receivedException) {
            $receivedException = $throwable;

            return 5;
        });

        $result = $middleware->handle($job, $next);

        $this->assertSame($job, $result);
        $this->assertSame($expectedException, $receivedException);
        $this->assertSame(300, $job->releasedAfter);
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
