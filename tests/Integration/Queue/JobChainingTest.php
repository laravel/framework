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
}

class JobChainingTestFirstJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class JobChainingTestSecondJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class JobChainingTestThirdJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
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
