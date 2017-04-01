<?php

use Illuminate\Bus\Queueable;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * @group integration
 */
class JobChainingTest extends TestCase
{
    public function tearDown()
    {
        JobChainingTestFirstJob::$ran = false;
        JobChainingTestSecondJob::$ran = false;
    }


    public function test_jobs_can_be_chained_on_success()
    {
        JobChainingTestFirstJob::dispatch()->then([
            new JobChainingTestSecondJob
        ]);

        $this->assertTrue(JobChainingTestFirstJob::$ran);
        $this->assertTrue(JobChainingTestSecondJob::$ran);
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
