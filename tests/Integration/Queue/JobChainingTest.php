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
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('queue.connections.sync1', [
            'driver' => 'sync',
        ]);

        $app['config']->set('queue.connections.sync2', [
            'driver' => 'sync',
        ]);
    }

    public function tearDown()
    {
        JobChainingTestFirstJob::$ran = false;
        JobChainingTestSecondJob::$ran = false;
        JobChainingTestThirdJob::$ran = false;
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

    public function test_chain_jobs_use_same_config()
    {
        JobChainingTestFirstJob::dispatch()->allOnQueue('some_queue')->allOnConnection('sync1')->chain([
            new JobChainingTestSecondJob,
            new JobChainingTestThirdJob,
        ]);

        $this->assertEquals('some_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertEquals('sync1', JobChainingTestFirstJob::$usedConnection);

        $this->assertEquals('some_queue', JobChainingTestSecondJob::$usedQueue);
        $this->assertEquals('sync1', JobChainingTestSecondJob::$usedConnection);

        $this->assertEquals('some_queue', JobChainingTestThirdJob::$usedQueue);
        $this->assertEquals('sync1', JobChainingTestThirdJob::$usedConnection);
    }

    public function test_chain_jobs_use_own_config()
    {
        JobChainingTestFirstJob::dispatch()->allOnQueue('some_queue')->allOnConnection('sync1')->chain([
            (new JobChainingTestSecondJob)->onQueue('another_queue')->onConnection('sync2'),
            new JobChainingTestThirdJob,
        ]);

        $this->assertEquals('some_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertEquals('sync1', JobChainingTestFirstJob::$usedConnection);

        $this->assertEquals('another_queue', JobChainingTestSecondJob::$usedQueue);
        $this->assertEquals('sync2', JobChainingTestSecondJob::$usedConnection);

        $this->assertEquals('some_queue', JobChainingTestThirdJob::$usedQueue);
        $this->assertEquals('sync1', JobChainingTestThirdJob::$usedConnection);
    }

    public function test_chain_jobs_use_default_config()
    {
        JobChainingTestFirstJob::dispatch()->onQueue('some_queue')->onConnection('sync1')->chain([
            (new JobChainingTestSecondJob)->onQueue('another_queue')->onConnection('sync2'),
            new JobChainingTestThirdJob,
        ]);

        $this->assertEquals('some_queue', JobChainingTestFirstJob::$usedQueue);
        $this->assertEquals('sync1', JobChainingTestFirstJob::$usedConnection);

        $this->assertEquals('another_queue', JobChainingTestSecondJob::$usedQueue);
        $this->assertEquals('sync2', JobChainingTestSecondJob::$usedConnection);

        $this->assertEquals(null, JobChainingTestThirdJob::$usedQueue);
        $this->assertEquals(null, JobChainingTestThirdJob::$usedConnection);
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
