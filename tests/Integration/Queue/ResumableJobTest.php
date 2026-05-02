<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\JobSequence\ExecutionStateOG;
use Illuminate\Bus\JobSequence\JobSequence;
use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\ResumableOG;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\ResumableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class ResumableJobTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cache.default', 'database');
        //$app['config']->set('queue.default', 'database');
    }

    public function test_job()
    {
        /*
         * TODO:
         * Container bindings for default persistence callbacks
         * Figure out where ExecutionState lives
         * Does Workflows need to live in its own space? or would inside of Bus be better?
         * Add a test where we set the state somewhere further down the line
         * Figure out the API surface for a JobSequence... how do they write the steps. Maybe we should just kill handle and execute that some other way
         * Try a job on the queue where we push the job and then release it after every pipe. does it work at all?
         *
         */
    }
}

class TestResumableOGJob implements ShouldQueue, ResumableOG
{
    use InteractsWithQueue;
    use ResumableTrait;
    use Dispatchable;

    public function handle()
    {
        // @todo
    }
}
