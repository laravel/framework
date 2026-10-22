<?php

namespace Illuminate\Tests\Integration\Queue;

use Exception;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Cache\Limiters\LimiterTimeoutException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\CallQueuedHandler;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\FakeJob;
use Illuminate\Queue\Middleware\ConcurrencyLimited;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ConcurrencyLimitedJobsTest extends QueueTestCase
{
    protected $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->key = Str::random();
    }

    protected function tearDown(): void
    {
        ConcurrencyLimitedTestJob::$handled = false;
        ConcurrencyLimitedTestJob::$limiter = null;
        ExpiringSlotTestJob::$slotWasFree = null;

        parent::tearDown();
    }

    public function testJobIsHandledWhenASlotIsAvailable()
    {
        ConcurrencyLimitedTestJob::$limiter = new ConcurrencyLimited($this->key, 2);

        $job = $this->dispatchJob($command = new ConcurrencyLimitedTestJob);

        $this->assertTrue(ConcurrencyLimitedTestJob::$handled);
        $this->assertTrue($job->isDeleted());
        $this->assertTrue($this->takeSlot($command, 1));
    }

    public function testJobIsHandledWhileOtherSlotsAreTaken()
    {
        ConcurrencyLimitedTestJob::$limiter = new ConcurrencyLimited($this->key, 3);

        $this->takeSlot($command = new ConcurrencyLimitedTestJob, 1);
        $this->takeSlot($command, 2);

        $job = $this->dispatchJob($command);

        $this->assertTrue(ConcurrencyLimitedTestJob::$handled);
        $this->assertTrue($job->isDeleted());
        $this->assertTrue($this->takeSlot($command, 3));
    }

    public function testJobIsReleasedWhenAllSlotsAreTaken()
    {
        ConcurrencyLimitedTestJob::$limiter = (new ConcurrencyLimited($this->key, 2))->releaseAfter(30);

        $this->takeSlot($command = new ConcurrencyLimitedTestJob, 1);
        $this->takeSlot($command, 2);

        $job = $this->dispatchJob($command);

        $this->assertFalse(ConcurrencyLimitedTestJob::$handled);
        $this->assertTrue($job->isReleased());
        $this->assertSame(30, $job->releaseDelay);
    }

    public function testJobIsNotReleasedWhenUsingDontRelease()
    {
        ConcurrencyLimitedTestJob::$limiter = (new ConcurrencyLimited($this->key, 1))->dontRelease();

        $this->takeSlot($command = new ConcurrencyLimitedTestJob, 1);

        $job = $this->dispatchJob($command);

        $this->assertFalse(ConcurrencyLimitedTestJob::$handled);
        $this->assertFalse($job->isReleased());
        $this->assertTrue($job->isDeleted());
    }

    public function testSlotIsReleasedWhenTheJobThrows()
    {
        ConcurrencyLimitedTestJob::$limiter = new ConcurrencyLimited($this->key, 1);

        try {
            $this->dispatchJob($command = new FailingConcurrencyLimitedTestJob);

            $this->fail('The exception was not thrown.');
        } catch (Exception) {
            //
        }

        $this->assertTrue(ConcurrencyLimitedTestJob::$handled);
        $this->assertTrue($this->takeSlot($command, 1));
    }

    public function testLimiterTimeoutsThrownByTheJobAreNotTreatedAsMissingSlots()
    {
        ConcurrencyLimitedTestJob::$limiter = new ConcurrencyLimited($this->key, 1);

        $job = new FakeJob;

        try {
            (new CallQueuedHandler(new Dispatcher($this->app), $this->app))->call($job, [
                'command' => serialize($command = new NestedLimiterConcurrencyLimitedTestJob),
            ]);

            $this->fail('The exception was not thrown.');
        } catch (LimiterTimeoutException) {
            //
        }

        $this->assertFalse($job->isReleased());
        $this->assertTrue($this->takeSlot($command, 1));
    }

    public function testSlotExpiresAfterTheGivenSeconds()
    {
        $this->app->instance(Cache::class, $this->app['cache']->store('array'));

        ConcurrencyLimitedTestJob::$limiter = (new ConcurrencyLimited($this->key, 1))->expireAfter(10);

        $this->dispatchJob(new ExpiringSlotTestJob);

        $this->assertTrue(ExpiringSlotTestJob::$slotWasFree);
    }

    public function testSlotsCanBeSharedAcrossJobs()
    {
        ConcurrencyLimitedTestJob::$limiter = (new ConcurrencyLimited($this->key, 1))->shared();

        $this->app->get(Cache::class)->lock('laravel-queue-concurrency:'.$this->key.':1', 10)->acquire();

        $job = $this->dispatchJob(new ConcurrencyLimitedTestJob);

        $this->assertFalse(ConcurrencyLimitedTestJob::$handled);
        $this->assertTrue($job->isReleased());
    }

    public function testSlotsOfSimilarKeysDoNotCollide()
    {
        ConcurrencyLimitedTestJob::$limiter = new ConcurrencyLimited($this->key.'1', 1);
        $this->takeSlot($command = new ConcurrencyLimitedTestJob, 1);

        ConcurrencyLimitedTestJob::$limiter = new ConcurrencyLimited($this->key, 11);
        foreach (range(1, 10) as $slot) {
            $this->takeSlot($command, $slot);
        }

        $job = $this->dispatchJob($command);

        $this->assertTrue(ConcurrencyLimitedTestJob::$handled);
        $this->assertTrue($job->isDeleted());
    }

    public function testGetLimiterKey()
    {
        $job = new ConcurrencyLimitedTestJob;

        $this->assertSame(
            'laravel-queue-concurrency:'.ConcurrencyLimitedTestJob::class.':stripe',
            (new ConcurrencyLimited('stripe', 5))->getLimiterKey($job)
        );

        $this->assertSame(
            'laravel-queue-concurrency:'.ConcurrencyLimitedTestJob::class.':openai',
            (new ConcurrencyLimited(ConcurrencyLimitedTestService::OpenAi, 5))->getLimiterKey($job)
        );

        $this->assertSame(
            'laravel-queue-concurrency:stripe',
            (new ConcurrencyLimited('stripe', 5))->shared()->getLimiterKey($job)
        );

        $this->assertSame(
            'prefix:stripe',
            (new ConcurrencyLimited('stripe', 5))->withPrefix('prefix:')->shared()->getLimiterKey($job)
        );
    }

    protected function dispatchJob($command)
    {
        $job = new FakeJob;

        (new CallQueuedHandler(new Dispatcher($this->app), $this->app))->call($job, [
            'command' => serialize($command),
        ]);

        return $job;
    }

    protected function takeSlot($command, $slot)
    {
        $key = ConcurrencyLimitedTestJob::$limiter->getLimiterKey($command);

        return $this->app->get(Cache::class)->lock($key.':'.$slot, 10)->acquire();
    }
}

enum ConcurrencyLimitedTestService: string
{
    case OpenAi = 'openai';
}

class ConcurrencyLimitedTestJob
{
    use InteractsWithQueue, Queueable;

    public static $handled = false;

    public static $limiter;

    public function handle()
    {
        static::$handled = true;
    }

    public function middleware()
    {
        return [static::$limiter];
    }
}

class FailingConcurrencyLimitedTestJob extends ConcurrencyLimitedTestJob
{
    public function handle()
    {
        static::$handled = true;

        throw new Exception;
    }
}

class NestedLimiterConcurrencyLimitedTestJob extends ConcurrencyLimitedTestJob
{
    public function handle()
    {
        throw new LimiterTimeoutException;
    }
}

class ExpiringSlotTestJob extends ConcurrencyLimitedTestJob
{
    public static $slotWasFree;

    public function handle()
    {
        Carbon::setTestNow(Carbon::now()->addSeconds(11));

        $key = static::$limiter->getLimiterKey($this);

        static::$slotWasFree = app(Cache::class)->lock($key.':1', 10)->acquire();

        Carbon::setTestNow();
    }
}
