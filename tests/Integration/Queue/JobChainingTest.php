<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class JobChainingTest extends TestCase
{
    public static $catchCallbackRan = false;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('queue.connections.sync1', [
            'driver' => 'sync',
        ]);

        $app['config']->set('queue.connections.sync2', [
            'driver' => 'sync',
        ]);
    }

    protected function tearDown(): void
    {
        JobChainingTestFirstJob::$ran = false;
        JobChainingTestSecondJob::$ran = false;
        JobChainingTestThirdJob::$ran = false;
        static::$catchCallbackRan = false;
    }

    public function testJobsCanBeChainedOnSuccess()
    {
        JobChainingTestFirstJob::dispatch()->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingPendingChain()
    {
        JobChainingTestFirstJob::withChain([
            new JobChainingTestSecondJob,
        ])->dispatch();

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingBusFacade()
    {
        Bus::dispatchChain([
            new JobChainingTestFirstJob,
            new JobChainingTestSecondJob,
        ]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingBusFacadeAsArguments()
    {
        Bus::dispatchChain(
            new JobChainingTestFirstJob,
            new JobChainingTestSecondJob
        );

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsChainedOnExplicitDelete()
    {
        JobChainingTestDeletingJob::dispatch()->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->assertTrue(JobChainingTestDeletingJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessWithSeveralJobs()
    {
        JobChainingTestFirstJob::dispatch()->chain([
            new JobChainingTestSecondJob,
            new JobChainingTestThirdJob,
        ]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
        $this->assertTrue(JobChainingTestThirdJob::$ran);
    }

    public function testJobsCanBeChainedOnSuccessUsingHelper()
    {
        dispatch(new JobChainingTestFirstJob)->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testJobsCanBeChainedViaQueue()
    {
        Queue::connection('sync')->push((new JobChainingTestFirstJob)->chain([
            new JobChainingTestSecondJob,
        ]));

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function testSecondJobIsNotFiredIfFirstFailed()
    {
        Queue::connection('sync')->push((new JobChainingTestFailingJob)->chain([
            new JobChainingTestSecondJob,
        ]));

        $this->assertFalse(JobChainingTestSecondJob::$ran);
    }

    public function testSecondJobIsNotFiredIfFirstReleased()
    {
        Queue::connection('sync')->push((new JobChainingTestReleasingJob)->chain([
            new JobChainingTestSecondJob,
        ]));

        $this->assertFalse(JobChainingTestSecondJob::$ran);
    }

    public function testThirdJobIsNotFiredIfSecondFails()
    {
        Queue::connection('sync')->push((new JobChainingTestFirstJob)->chain([
            new JobChainingTestFailingJob,
            new JobChainingTestThirdJob,
        ]));

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertFalse(JobChainingTestThirdJob::$ran);
    }

    public function testCatchCallbackIsCalledOnFailure()
    {
        Bus::chain([
            new JobChainingTestFirstJob,
            new JobChainingTestFailingJob,
            new JobChainingTestSecondJob,
        ])->catch(static function () {
            self::$catchCallbackRan = true;
        })->dispatch();

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(static::$catchCallbackRan);
        $this->assertFalse(JobChainingTestSecondJob::$ran);
    }

    public function testChainJobsUseSameConfig()
    {
        JobChainingTestFirstJob::dispatch()->allOnQueue('some_queue')->allOnConnection('sync1')->chain([
            new JobChainingTestSecondJob,
            new JobChainingTestThirdJob,
        ]);

        $this->assertSame('some_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestFirstJob::$usedConnection);

        $this->assertSame('some_queue', JobChainingTestSecondJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestSecondJob::$usedConnection);

        $this->assertSame('some_queue', JobChainingTestThirdJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestThirdJob::$usedConnection);
    }

    public function testChainJobsUseOwnConfig()
    {
        JobChainingTestFirstJob::dispatch()->allOnQueue('some_queue')->allOnConnection('sync1')->chain([
            (new JobChainingTestSecondJob)->onQueue('another_queue')->onConnection('sync2'),
            new JobChainingTestThirdJob,
        ]);

        $this->assertSame('some_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestFirstJob::$usedConnection);

        $this->assertSame('another_queue', JobChainingTestSecondJob::$usedQueue);
        $this->assertSame('sync2', JobChainingTestSecondJob::$usedConnection);

        $this->assertSame('some_queue', JobChainingTestThirdJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestThirdJob::$usedConnection);
    }

    public function testChainJobsUseDefaultConfig()
    {
        JobChainingTestFirstJob::dispatch()->onQueue('some_queue')->onConnection('sync1')->chain([
            (new JobChainingTestSecondJob)->onQueue('another_queue')->onConnection('sync2'),
            new JobChainingTestThirdJob,
        ]);

        $this->assertSame('some_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertSame('sync1', JobChainingTestFirstJob::$usedConnection);

        $this->assertSame('another_queue', JobChainingTestSecondJob::$usedQueue);
        $this->assertSame('sync2', JobChainingTestSecondJob::$usedConnection);

        $this->assertNull(JobChainingTestThirdJob::$usedQueue);
        $this->assertNull(JobChainingTestThirdJob::$usedConnection);
    }
}

class JobChainingTestFirstJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;
    public static $usedQueue = null;
    public static $usedConnection = null;

    public function handle()
    {
        static::$ran = true;
        static::$usedQueue = $this->queue;
        static::$usedConnection = $this->connection;
    }
}

class JobChainingTestSecondJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;
    public static $usedQueue = null;
    public static $usedConnection = null;

    public function handle()
    {
        static::$ran = true;
        static::$usedQueue = $this->queue;
        static::$usedConnection = $this->connection;
    }
}

class JobChainingTestThirdJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;
    public static $usedQueue = null;
    public static $usedConnection = null;

    public function handle()
    {
        static::$ran = true;
        static::$usedQueue = $this->queue;
        static::$usedConnection = $this->connection;
    }
}

class JobChainingTestDeletingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
        $this->delete();
    }
}

class JobChainingTestReleasingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        $this->release(30);
    }
}

class JobChainingTestFailingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        $this->fail();
    }
}
