<?php

namespace Illuminate\Bus\ExecutionContext;

use Illuminate\Bus\Events\StepCompleted;
use Illuminate\Bus\Events\StepStarting;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Workflow\ExecutionRepository as ExecutionRepositoryContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ExecutionContext
{
    protected ExecutionState $state;

    public function __construct(
        protected ExecutionRepositoryContract $executionRepository,
        protected ?Dispatcher $eventDispatcher = null,
        mixed $stateId = null,
    ) {
        if ($stateId instanceof ExecutionState) {
            $this->state = $stateId;
        }
        else {
            $this->state = $this->executionRepository->find($stateId)
                ?? $this->executionRepository->create($stateId ?? Str::random(32));
        }
    }

    public function step(string $name, callable $callback): mixed
    {
        if ($this->state->hasCompletedStep($name)) {
            return $this->state->resultFor($name);
        }

        $this->eventDispatcher?->dispatch(new StepStarting($this->state, $name));

        $result = $callback();

        $this->state->recordStepResult($name, $result, $completedAt = Carbon::now()->getTimestamp());

        $this->eventDispatcher?->dispatch(new StepCompleted($this->state, $name, $result, $completedAt));

        $this->executionRepository->saveStep($this->state, $name);

        return $result;
    }
}
