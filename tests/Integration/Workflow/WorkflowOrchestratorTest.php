<?php

namespace Illuminate\Tests\Integration\Workflow;

use Illuminate\Workflow\Workflow;
use Orchestra\Testbench\TestCase;

class WorkflowOrchestratorTest extends TestCase
{
    public function test_workflow(): void
    {
        $stepper = new WorkflowStepper();
        $workflow = $this->app->make(Workflow::class)
            ->withState([
                'foo' => true,
                'bar' => false,
            ])->withStep('step1', $stepper->step1(...));
        $workflow->handle('step1');

        $this->assertTrue($workflow->state['bar']);
        $this->assertEquals(['step1' => 1], WorkflowStepper::$timesCalled);
    }

    public function test_workflow_orchestrator()
    {

    }
}

class WorkflowStepper
{
    public static array $timesCalled = [];
    public function __construct()
    {
        self::$timesCalled = [];
    }

    public function step1(array $state)
    {
        self::$timesCalled['step1'] ??= 0;
        self::$timesCalled['step1']++;

        $state['bar'] = true;

        return $state;
    }
}
