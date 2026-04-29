<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\ResumeState;
use Illuminate\Bus\ResumeStateRepository;
use Illuminate\Contracts\Queue\Resumable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Queue;
use Illuminate\Queue\ResumableTrait;
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
        TestResumableJob::dispatch();
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

class WorkflowTest implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(ResumeStateRepository $resumeStateRepository, Pipeline $pipeline): void
    {
        $pipeline = new Pipeline(app());

    }
}

class WorkflowPipeline extends Pipeline
{
    protected \Closure $persistenceCallback;

    public function persistCallback(\Closure $callback): static
    {
        $this->persistenceCallback = $callback;

        return $this;
    }

    #[\Override]
    protected function handleCarry(mixed $carry)
    {
        if (isset($this->persistenceCallback)) {
            call_user_func($this->persistenceCallback, $carry);
        }

        return $carry;
    }
}

class Workflow
{
    public array $steps = [];
    public array $orderedSteps = [];

    public ResumeState $state;

    protected \Closure $persistenceCallback;

    protected function buildStepName()
    {
        return 'workflow_step'.count($this->steps);
    }

    public function persistenceCallback(\Closure $callback): static
    {
        $this->persistenceCallback = $callback;

        return $this;
    }

    public function addStep(\Closure $callback, ?string $name = null): static
    {
        $name ??= $this->buildStepName();
        $this->state->orderedSteps[$name] = $name;
        $this->orderedSteps[] = $name;
        $this->steps[$name] = $callback;

        return $this;
    }

    public function withState(ResumeState $resumeState): static
    {
        $this->state = $resumeState;

        return $this;
    }

    public function execute()
    {
        $pipeline = new WorkflowPipeline(app());

        $state = $this->state ?? new ResumeState();

        $pipeline->persistCallback($this->persistenceCallback)->send($state);

        // Figure out which pipe we are on
        $remainingSteps = array_slice($this->orderedSteps, $this->state->stepIndex);

        if ($remainingSteps === []) {
            // throw an exception? not sure
            throw new \Exception("how did this happen?");
        }

    }
}
