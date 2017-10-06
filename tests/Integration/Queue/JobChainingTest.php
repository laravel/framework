<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * @group integration
 */
class JobChainingTest extends TestCase
{
    public function tearDown()
    {
        JobChainingTestFirstJob::$ran = false;
        JobChainingTestFirstJob::$usedQueue = null;
        JobChainingTestFirstJob::$usedConnection = null;
        JobChainingTestFirstJob::$usedChainedQueue = null;
        JobChainingTestFirstJob::$usedChainedConnection = null;
        JobChainingTestSecondJob::$ran = false;
        JobChainingTestSecondJob::$usedQueue = null;
        JobChainingTestSecondJob::$usedConnection = null;
        JobChainingTestSecondJob::$usedChainedQueue = null;
        JobChainingTestSecondJob::$usedChainedConnection = null;
        JobChainingTestThirdJob::$ran = false;
        JobChainingTestThirdJob::$usedQueue = null;
        JobChainingTestThirdJob::$usedConnection = null;
        JobChainingTestThirdJob::$usedChainedQueue = null;
        JobChainingTestThirdJob::$usedChainedConnection = null;
    }

    public function test_jobs_can_be_chained_on_success()
    {
        JobChainingTestFirstJob::dispatch()->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function test_jobs_can_be_chained_on_success_using_pending_chain()
    {
        JobChainingTestFirstJob::withChain([
            new JobChainingTestSecondJob,
        ])->dispatch();

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function test_jobs_chained_on_explicit_delete()
    {
        JobChainingTestDeletingJob::dispatch()->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->assertTrue(JobChainingTestDeletingJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function test_jobs_can_be_chained_on_success_with_several_jobs()
    {
        JobChainingTestFirstJob::dispatch()->chain([
            new JobChainingTestSecondJob,
            new JobChainingTestThirdJob,
        ]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
        $this->assertTrue(JobChainingTestThirdJob::$ran);
    }

    public function test_jobs_can_be_chained_on_success_using_helper()
    {
        dispatch(new JobChainingTestFirstJob)->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function test_jobs_can_be_chained_via_queue()
    {
        Queue::connection('sync')->push((new JobChainingTestFirstJob)->chain([
            new JobChainingTestSecondJob,
        ]));

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
    }

    public function test_jobs_can_be_chained_with_settitngs_via_queue()
    {
        Queue::connection('sync')->push((new JobChainingTestFirstJob)->chain([
            new JobChainingTestSecondJob,
        ], 'chain_queue_name', 'sync'));

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
        $this->assertEquals('chain_queue_name', JobChainingTestSecondJob::$usedQueue);
        $this->assertEquals('sync', JobChainingTestSecondJob::$usedConnection);
    }

    public function test_second_job_is_not_fired_if_first_failed()
    {
        Queue::connection('sync')->push((new JobChainingTestFailingJob)->chain([
            new JobChainingTestSecondJob,
        ]));

        $this->assertFalse(JobChainingTestSecondJob::$ran);
    }

    public function test_second_job_is_not_fired_if_first_released()
    {
        Queue::connection('sync')->push((new JobChainingTestReleasingJob)->chain([
            new JobChainingTestSecondJob,
        ]));

        $this->assertFalse(JobChainingTestSecondJob::$ran);
    }

    public function test_third_job_is_not_fired_if_second_fails()
    {
        Queue::connection('sync')->push((new JobChainingTestFirstJob)->chain([
            new JobChainingTestFailingJob,
            new JobChainingTestThirdJob,
        ]));

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertFalse(JobChainingTestThirdJob::$ran);
    }

    public function test_chain_jobs_use_default_config()
    {
        JobChainingTestFirstJob::dispatch()->onQueue('some_queue')->onConnection('sync')->chain([
            new JobChainingTestSecondJob,
        ]);

        $this->assertEquals(null, JobChainingTestSecondJob::$usedQueue);
        $this->assertEquals(null, JobChainingTestSecondJob::$usedConnection);
    }

    public function test_withchain_jobs_use_default_config()
    {
        JobChainingTestFirstJob::withChain([
            new JobChainingTestSecondJob,
        ])->dispatch()->onQueue('some_queue')->onConnection('sync');

        $this->assertEquals(null, JobChainingTestSecondJob::$usedQueue);
        $this->assertEquals(null, JobChainingTestSecondJob::$usedConnection);
    }

    public function test_chain_jobs_use_own_queue_config()
    {
        JobChainingTestFirstJob::dispatch()->onQueue('some_queue')->onConnection('sync')->chain([
            (new JobChainingTestSecondJob)->onQueue('another_queue'),
        ]);

        $this->assertEquals('some_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertEquals('another_queue', JobChainingTestSecondJob::$usedQueue);
    }

    public function test_chain_jobs_use_own_connection_config()
    {
        JobChainingTestFirstJob::dispatch()->onQueue('some_queue')->chain([
            (new JobChainingTestSecondJob)->onConnection('sync'),
        ]);

        $this->assertEquals(null, JobChainingTestFirstJob::$usedConnection);
        $this->assertEquals('sync', JobChainingTestSecondJob::$usedConnection);
    }

    public function test_chain_jobs_use_own_connection_and_queue_config()
    {
        JobChainingTestFirstJob::dispatch()->onQueue('some_queue')->chain([
            (new JobChainingTestSecondJob)->onQueue('another_queue')->onConnection('sync'),
        ]);

        $this->assertEquals(null, JobChainingTestFirstJob::$usedConnection);
        $this->assertEquals('sync', JobChainingTestSecondJob::$usedConnection);

        $this->assertEquals('some_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertEquals('another_queue', JobChainingTestSecondJob::$usedQueue);
    }

    public function test_chain_jobs_use_chain_settings()
    {
        JobChainingTestFirstJob::withChain([
            new JobChainingTestSecondJob,
        ], 'chain_queue_name', 'sync')->dispatch()->onQueue('first_queue')->onConnection('sync');

        $this->assertEquals('first_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertEquals('sync', JobChainingTestFirstJob::$usedConnection);

        $this->assertEquals('chain_queue_name', JobChainingTestSecondJob::$usedQueue);
        $this->assertEquals('sync', JobChainingTestSecondJob::$usedConnection);
    }

    public function test_chain_jobs_use_own_settings()
    {
        JobChainingTestFirstJob::withChain([
            (new JobChainingTestSecondJob)->onQueue('another_queue')->onConnection('sync'),
        ], 'chain_queue_name', 'sync')->dispatch()->onQueue('first_queue')->onConnection('sync');

        $this->assertEquals('first_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertEquals('sync', JobChainingTestFirstJob::$usedConnection);

        $this->assertEquals('another_queue', JobChainingTestSecondJob::$usedQueue);
        $this->assertEquals('sync', JobChainingTestSecondJob::$usedConnection);
    }

    public function test_chain_jobs_use_override_chain_settings()
    {
        JobChainingTestFirstJob::withChain([
            (new JobChainingTestSecondJob)->onChainQueue('override_chain_queue')->onChainConnection('sync'),
            (new JobChainingTestThirdJob),
        ], 'chain_queue_name', 'sync')->dispatch()->onQueue('first_queue')->onConnection('sync');

        $this->assertEquals('first_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertEquals('sync', JobChainingTestFirstJob::$usedConnection);

        $this->assertEquals('chain_queue_name', JobChainingTestSecondJob::$usedQueue);
        $this->assertEquals('sync', JobChainingTestSecondJob::$usedConnection);

        $this->assertEquals('override_chain_queue', JobChainingTestThirdJob::$usedQueue);
        $this->assertEquals('sync', JobChainingTestThirdJob::$usedConnection);
    }

}

class JobChainingTestFirstJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;
    public static $usedQueue = null;
    public static $usedConnection = null;
    public static $usedChainedQueue = null;
    public static $usedChainedConnection = null;

    public function handle()
    {
        static::$ran = true;
        static::$usedQueue = $this->queue;
        static::$usedConnection = $this->connection;
        static::$usedChainedQueue = $this->chain_queue;
        static::$usedChainedConnection = $this->chain_connection;
    }
}

class JobChainingTestSecondJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;
    public static $usedQueue = null;
    public static $usedConnection = null;
    public static $usedChainedQueue = null;
    public static $usedChainedConnection = null;

    public function handle()
    {
        static::$ran = true;
        static::$usedQueue = $this->queue;
        static::$usedConnection = $this->connection;
        static::$usedChainedQueue = $this->chain_queue;
        static::$usedChainedConnection = $this->chain_connection;
    }
}

class JobChainingTestThirdJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;
    public static $usedQueue = null;
    public static $usedConnection = null;
    public static $usedChainedQueue = null;
    public static $usedChainedConnection = null;

    public function handle()
    {
        static::$ran = true;
        static::$usedQueue = $this->queue;
        static::$usedConnection = $this->connection;
        static::$usedChainedQueue = $this->chain_queue;
        static::$usedChainedConnection = $this->chain_connection;
    }
}

class JobChainingTestDeletingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public static $ran = false;
    public static $usedQueue = null;
    public static $usedConnection = null;
    public static $usedChainedQueue = null;
    public static $usedChainedConnection = null;

    public function handle()
    {
        static::$ran = true;
        static::$usedQueue = $this->queue;
        static::$usedConnection = $this->connection;
        static::$usedChainedQueue = $this->chain_queue;
        static::$usedChainedConnection = $this->chain_connection;
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
