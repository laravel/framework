<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\JobSequence\ExecutionStateOG;
use Illuminate\Bus\JobSequence\JobSequence;
use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Resumable;
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
class ResumableJobOGTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cache.default', 'database');
        //$app['config']->set('queue.default', 'database');
    }

    protected function setUp(): void
    {
        StateHolder::$data = [];
        parent::setUp();
    }

    public function test_workflow()
    {
        // @todo move this to a unit test
        $workflow = $this->app->make(JobSequence::class);
        $store = new ArrayStore();
        $workflow->step(function (ExecutionStateOG $state) {
            $state->set('hello', 'world');
        })->step(function (ExecutionStateOG $state) {
            $state->name = 'luke';
        }, 'step2')->step(function (ExecutionStateOG $state) {
            $state->name = 'taylor';
        });
        $workflow->persistenceCallback(fn (ExecutionStateOG $state) => $store->put('resume', $state, 100));
        $workflow->clearStateCallback(function ($state) use ($store) {
            $this->assertEquals([
                'hello' => 'world',
                'name' => 'taylor',
            ], $state->data());
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
         * Figure out where ExecutionState lives
         * Does Workflows need to live in its own space? or would inside of Bus be better?
         * Add a test where we set the state somewhere further down the line
         * Figure out the API surface for a JobSequence... how do they write the steps. Maybe we should just kill handle and execute that some other way
         * Try a job on the queue where we push the job and then release it after every pipe. does it work at all?
         *
         */
    }

    public function test_dispatchedJob()
    {
        TestResumableJob::dispatch();
        $this->assertCount(2, StateHolder::$data);
        [$resumeState1, $resumeState2] = StateHolder::$data;
        $this->assertInstanceOf(ExecutionStateOG::class, $resumeState1);
        $this->assertSame(0, $resumeState1->stepIndex);
        $this->assertSame([], $resumeState1->data());
        $this->assertInstanceOf(ExecutionStateOG::class, $resumeState2);
        $this->assertSame(1, $resumeState2->stepIndex);
        $this->assertSame(['abc' => 123, 'xyz' => 456], $resumeState2->data());
        $this->assertEmpty(DB::table('cache')->get());
    }

    public function testRepeatingStep()
    {
        TestRepeatingStepResumableJob::dispatch();

        $this->assertCount(3, StateHolder::$data);
        $this->assertSame(0, StateHolder::$data[0]->stepIndex);
        $this->assertSame([], StateHolder::$data[0]->data());
        $this->assertSame(1, StateHolder::$data[1]->stepIndex);
        $this->assertSame(['abc' => 123, 'xyz' => 456], StateHolder::$data[1]->data());
        $this->assertSame(2, StateHolder::$data[2]->stepIndex);
        $this->assertSame(['abc' => 123, 'xyz' => 456], StateHolder::$data[2]->data());
    }
}

class StateHolder
{
    /** @var list<ExecutionStateOG> */
    public static array $data;
}

class TestResumableJob implements ShouldQueue, Resumable
{
    use InteractsWithQueue;
    use ResumableTrait;
    use Dispatchable;

    public function handle()
    {
        $this->sequence
            ->step(function (ExecutionStateOG $state) {
                StateHolder::$data[$state->stepIndex] = clone $state;
                $state->set('abc',  123);
            })->step(function (ExecutionStateOG $state) {
                $state->set('xyz', 456);
                StateHolder::$data[$state->stepIndex] = clone $state;
            }, 'step2');
    }
}

class TestRepeatingStepResumableJob extends TestResumableJob
{
    public function handle(): void
    {
        parent::handle();
        $this->sequence->step(name: 'step2');
    }
}

class CheckForUpdate implements ShouldQueue, Resumable
{
    use InteractsWithQueue;
    use ResumableTrait;
    use Dispatchable;

    public function handle(): void
    {
        $this->sequence
            // The state of the job is persisted in cache after each step
            // if there's a failure or an interrupt, the job will requeue
            // and start from the failure.
            ->step($this->getData(...))
            ->step($this->persistData(...))
            ->step($this->sendEmail(...));
    }

    private function getData(ExecutionStateOG $resumeState): void
    {
        $resumeState->data()['response'] = Http::get('https://jobs.laravel.com/jobs/')->json();
    }

    private function persistData(ExecutionStateOG $resumeState): void
    {
        foreach($resumeState->data['response']['data'] as $jobData) {
            DB::table('laravel_jobs')->insertGetId([
                'id' => $jobData['id'],
                'data' => json_encode($jobData),
            ]);
        }
    }

    private function sendEmail(ExecutionStateOG $resumeState): void
    {
        Mail::raw("New jobs\n", fn ($message) => $message
            ->to('luke@kuzmish.com')
            ->subject('Demo Email')
        );
    }
}

class LaravelJobNotification implements Mailable
{
    public function __construct($value)
    {
        $this->value = $value;
    }
    public function send($mailer)
    {
        // TODO: Implement send() method.
    }

    public function queue(Factory $queue)
    {
        // TODO: Implement queue() method.
    }

    public function later($delay, Factory $queue)
    {
        // TODO: Implement later() method.
    }

    public function cc($address, $name = null)
    {
        // TODO: Implement cc() method.
    }

    public function bcc($address, $name = null)
    {
        // TODO: Implement bcc() method.
    }

    public function to($address, $name = null)
    {
        // TODO: Implement to() method.
    }

    public function locale($locale)
    {
        // TODO: Implement locale() method.
    }

    public function mailer($mailer)
    {
        // TODO: Implement mailer() method.
    }
}
