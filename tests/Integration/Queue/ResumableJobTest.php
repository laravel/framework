<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\ResumeState;
use Illuminate\Bus\ResumeStateRepository;
use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Resumable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Queue;
use Illuminate\Queue\ResumableTrait;
use Illuminate\Workflow\Workflow;
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

    public function test_workflow()
    {
        $workflow = $this->app->make(Workflow::class);
        $store = new ArrayStore();
        $workflow->addStep(function (ResumeState $state) {
            $state->data['hello'] = 'world';
        })->addStep(function (ResumeState $state) {
            $state->data['name'] = 'luke';
        }, 'step2')->addStep(function (ResumeState $state) {
            $state->data['name'] = 'taylor';
        });
        $workflow->persistenceCallback(fn (ResumeState $state) => $store->put('resume', $state, 100));
        $workflow->clearStateCallback(function ($state) use ($store) {
            $this->assertEquals([
                'hello' => 'world',
                'name' => 'taylor',
            ], $state->stateData);
            $this->assertEquals(3, $state->stepIndex);
            $store->forget('resume');
        });
        $workflow->execute();

        $this->assertEmpty($store->all());
    }

    public function test_job()
    {
        /*
         * TODO:
         * Container bindings for default persistence callbacks
         * Figure out where ResumeState lives
         * Does Workflows need to live in its own space? or would inside of Bus be better?
         * Add a test where we set the state somewhere further down the line
         * Figure out the API surface for a Workflow... how do they write the steps. Maybe we should just kill handle and execute that some other way
         * Try a job on the queue where we push the job and then release it after every pipe. does it work at all?
         *
         */
    }
}

class TestResumableJob implements Resumable, ShouldQueue
{
    use InteractsWithQueue;
    use Dispatchable;
    use ResumableTrait;

    private array $called = [];

    public function handle()
    {
        $this->withStep('step1', $this->step1(...));
        $this->withStep('step2', function () {
            $this->called['anonymous'] = true;
        });
    }

    private function step1()
    {
        $this->called['step'] = true;
    }
}
