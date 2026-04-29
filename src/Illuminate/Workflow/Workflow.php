<?php

namespace Illuminate\Workflow;

use Illuminate\Bus\ResumeState;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;

class Workflow
{
    public array $steps = [];
    public array $orderedSteps = [];

    public ResumeState $state;

    protected \Closure $persistenceCallback;

    protected \Closure $clearStateCallback;

    public function __construct(
        protected Container $container,
    ) {
    }

    protected function buildStepName(): string
    {
        return 'workflow_step'.count($this->steps);
    }

    public function persistenceCallback(\Closure $callback): static
    {
        $this->persistenceCallback = $callback;

        return $this;
    }

    public function clearStateCallback(\Closure $callback): static
    {
        $this->clearStateCallback = $callback;

        return $this;
    }

    public function addStep(\Closure $callback, ?string $name = null): static
    {
        $name ??= $this->buildStepName();
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
        $this->state ??= new ResumeState();

        $pipeline = new Pipeline($this->container);
        $pipeline->send($this->state);

        // Figure out which pipe we are on
        $remainingSteps = array_slice($this->orderedSteps, $this->state->stepIndex);

        if ($remainingSteps === []) {
            // throw an exception? not sure
            throw new \LogicException('There are no remaining steps.');
        }

        foreach ($remainingSteps as $stepName) {
            $fn = $this->steps[$stepName];

            $pipeline->pipe(function (ResumeState $carry, $next) use ($fn) {
                $fn($carry);
                $carry->stepIndex++;

                call_user_func($this->persistenceCallback, $carry);

                return $next($carry);
            });
        }

        $result = $pipeline->thenReturn();

        $state = $result instanceof ResumeState ? $result : $this->state;
        call_user_func($this->clearStateCallback, $state);

        return $result;
    }
}
