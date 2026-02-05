<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('queue')]
class BatchClosureTest extends QueueTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            BatchClosureJobRecorder::reset();
        });

        parent::setUp();
    }

    public function testBatchWithClosureAndThenCallback()
    {
        Bus::batch(function () {
            Bus::dispatch(new BatchClosureTestJob('job1'));
            Bus::dispatch(new BatchClosureTestJob('job2'));
        })->then(function () {
            BatchClosureJobRecorder::record('then');
        })->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertEquals(['job1', 'job2', 'then'], BatchClosureJobRecorder::$results);
    }

    public function testNestedBatchWithClosureAndThenCallbacks()
    {
        Bus::batch(function () {
            Bus::dispatch(new BatchClosureTestJob('outer'));

            Bus::batch(function () {
                Bus::dispatch(new BatchClosureTestJob('inner'));
            })->then(function () {
                BatchClosureJobRecorder::record('inner-then');
            })->dispatch();
        })->then(function () {
            BatchClosureJobRecorder::record('outer-then');
        })->dispatch();

        $this->runQueueWorkerCommand(['--stop-when-empty' => true]);

        $this->assertEquals(['outer', 'inner', 'inner-then', 'outer-then'], BatchClosureJobRecorder::$results);
    }
}

class BatchClosureTestJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public string $value)
    {
    }

    public function handle()
    {
        BatchClosureJobRecorder::record($this->value);
    }
}

class BatchClosureJobRecorder
{
    public static $results = [];

    public static function record(string $id)
    {
        self::$results[] = $id;
    }

    public static function reset()
    {
        self::$results = [];
    }
}
