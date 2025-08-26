<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Support\Carbon;
use Mockery as m;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class ThrottlesExceptionsTest extends TestCase
{
    public function testCircuitIsOpenedForJobErrors()
    {
        $this->assertJobWasReleasedImmediately(CircuitBreakerTestJob::class);
        $this->assertJobWasReleasedImmediately(CircuitBreakerTestJob::class);
        $this->assertJobWasReleasedWithDelay(CircuitBreakerTestJob::class);
    }

    public function testCircuitStaysClosedForSuccessfulJobs()
    {
        $this->assertJobRanSuccessfully(CircuitBreakerSuccessfulJob::class);
        $this->assertJobRanSuccessfully(CircuitBreakerSuccessfulJob::class);
        $this->assertJobRanSuccessfully(CircuitBreakerSuccessfulJob::class);
    }

    public function testCircuitResetsAfterSuccess()
    {
        $this->assertJobWasReleasedImmediately(CircuitBreakerTestJob::class);
        $this->assertJobRanSuccessfully(CircuitBreakerSuccessfulJob::class);
        $this->assertJobWasReleasedImmediately(CircuitBreakerTestJob::class);
        $this->assertJobWasReleasedImmediately(CircuitBreakerTestJob::class);
        $this->assertJobWasReleasedWithDelay(CircuitBreakerTestJob::class);
    }

    public function testCircuitCanSkipJob()
    {
        $this->assertJobWasDeleted(CircuitBreakerSkipJob::class);
    }

    public function testCircuitCanFailJob()
    {
        $this->assertJobWasFailed(CircuitBreakerFailedJob::class);
    }

    protected function assertJobWasReleasedImmediately($class)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('release')->with(0)->once();
        $job->shouldReceive('isReleased')->andReturn(true);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(true);
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertTrue($class::$handled);
    }

    protected function assertJobWasReleasedWithDelay($class)
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
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertFalse($class::$handled);
    }

    protected function assertJobWasDeleted($class)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('isDeleted')->andReturn(true);
        $job->shouldReceive('isReleased')->twice()->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(true);
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertTrue($class::$handled);
    }

    protected function assertJobWasFailed($class)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(true);
        $job->shouldReceive('fail')->once();
        $job->shouldReceive('isDeleted')->andReturn(true);
        $job->shouldReceive('isReleased')->once()->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(true);
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertTrue($class::$handled);
    }

    protected function assertJobRanSuccessfully($class)
    {
        $class::$handled = false;
        $instance = new CallQueuedHandler(new Dispatcher($this->app), $this->app);

        $job = m::mock(Job::class);

        $job->shouldReceive('hasFailed')->once()->andReturn(false);
        $job->shouldReceive('isReleased')->andReturn(false);
        $job->shouldReceive('isDeletedOrReleased')->once()->andReturn(false);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('uuid')->andReturn('simple-test-uuid');

        $instance->call($job, [
            'command' => serialize($command = new $class),
        ]);

        $this->assertTrue($class::$handled);
    }

    public function testItCanLimitPerMinute()
    {
        $jobFactory = fn () => new class
        {
            public $released = false;

            public $handled = false;

            public function release()
            {
                $this->released = true;

                return $this;
            }
        };
        $next = function ($job) {
            $job->handled = true;

            throw new RuntimeException('Whoops!');
        };

        $middleware = new ThrottlesExceptions(3, 60);

        Carbon::setTestNow('2000-00-00 00:00:00.000');

        for ($i = 0; $i < 3; $i++) {
            $result = $middleware->handle($job = $jobFactory(), $next);
            $this->assertSame($job, $result);
            $this->assertTrue($job->released);
            $this->assertTrue($job->handled);

            Carbon::setTestNow(now()->addSeconds(1));
        }

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertSame($job, $result);
        $this->assertTrue($job->released);
        $this->assertFalse($job->handled);

        Carbon::setTestNow('2000-00-00 00:00:59.999');

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertSame($job, $result);
        $this->assertTrue($job->released);
        $this->assertFalse($job->handled);

        Carbon::setTestNow('2000-00-00 00:01:00.000');

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertSame($job, $result);
        $this->assertTrue($job->released);
        $this->assertTrue($job->handled);
    }

    public function testItCanLimitPerSecond()
    {
        $jobFactory = fn () => new class
        {
            public $released = false;

            public $handled = false;

            public function release()
            {
                $this->released = true;

                return $this;
            }
        };
        $next = function ($job) {
            $job->handled = true;

            throw new RuntimeException('Whoops!');
        };

        $middleware = new ThrottlesExceptions(3, 1);

        Carbon::setTestNow('2000-00-00 00:00:00.000');

        for ($i = 0; $i < 3; $i++) {
            $result = $middleware->handle($job = $jobFactory(), $next);
            $this->assertSame($job, $result);
            $this->assertTrue($job->released);
            $this->assertTrue($job->handled);

            Carbon::setTestNow(now()->addMilliseconds(100));
        }

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertSame($job, $result);
        $this->assertTrue($job->released);
        $this->assertFalse($job->handled);

        Carbon::setTestNow('2000-00-00 00:00:00.999');

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertSame($job, $result);
        $this->assertTrue($job->released);
        $this->assertFalse($job->handled);

        Carbon::setTestNow('2000-00-00 00:00:01.000');

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertSame($job, $result);
        $this->assertTrue($job->released);
        $this->assertTrue($job->handled);
    }

    public function testLimitingWithDefaultValues()
    {
        $jobFactory = fn () => new class
        {
            public $released = false;

            public $handled = false;

            public function release()
            {
                $this->released = true;

                return $this;
            }
        };
        $next = function ($job) {
            $job->handled = true;

            throw new RuntimeException('Whoops!');
        };

        $middleware = new ThrottlesExceptions();

        Carbon::setTestNow('2000-00-00 00:00:00.000');

        for ($i = 0; $i < 10; $i++) {
            $result = $middleware->handle($job = $jobFactory(), $next);
            $this->assertSame($job, $result);
            $this->assertTrue($job->released);
            $this->assertTrue($job->handled);

            Carbon::setTestNow(now()->addSeconds(1));
        }

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertSame($job, $result);
        $this->assertTrue($job->released);
        $this->assertFalse($job->handled);

        Carbon::setTestNow('2000-00-00 00:09:59.999');

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertSame($job, $result);
        $this->assertTrue($job->released);
        $this->assertFalse($job->handled);

        Carbon::setTestNow('2000-00-00 00:10:00.000');

        $result = $middleware->handle($job = $jobFactory(), $next);
        $this->assertSame($job, $result);
        $this->assertTrue($job->released);
        $this->assertTrue($job->handled);
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

        $middleware = new ThrottlesExceptions();

        $middleware->report();
        $middleware->handle($job, $next);

        $middleware->report(fn () => true);
        $middleware->handle($job, $next);

        $middleware->report(fn () => false);
        $middleware->handle($job, $next);
    }
}

class CircuitBreakerTestJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }

    public function middleware()
    {
        return [(new ThrottlesExceptions(2, 10 * 60))->by('test')];
    }
}

class CircuitBreakerSkipJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }

    public function middleware()
    {
        return [(new ThrottlesExceptions(2, 10 * 60))->deleteWhen(Exception::class)];
    }
}

class CircuitBreakerFailedJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }

    public function middleware()
    {
        return [(new ThrottlesExceptions(2, 10 * 60))->failWhen(Exception::class)];
    }
}

class CircuitBreakerSuccessfulJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [(new ThrottlesExceptions(2, 10 * 60))->by('test')];
    }
}
