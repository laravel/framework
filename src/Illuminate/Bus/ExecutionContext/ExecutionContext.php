<?php

namespace Illuminate\Bus\ExecutionContext;

use Illuminate\Bus\Events\StepCompleted;
use Illuminate\Bus\Events\StepFailed;
use Illuminate\Bus\Events\StepStarting;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Workflow\ExecutionRepository as ExecutionRepositoryContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class ExecutionContext
{
    protected ExecutionState $state;

    /**
     * @param  ExecutionRepositoryContract  $executionRepository
     * @param  Dispatcher|null  $eventDispatcher
     * @param  mixed|null  $id
     * @param  array{ttl?: \DateTimeInterface|\DateInterval|int|null}  $options
     */
    public function __construct(
        protected ExecutionRepositoryContract $executionRepository,
        protected ?Dispatcher $eventDispatcher = null,
        mixed $id = null,
        protected $options = [],
    ) {
        if ($id instanceof ExecutionState) {
            $this->state = $id;
        } else {
            $this->state = $this->executionRepository->find($id)
                ?? $this->executionRepository->create($id ?? Str::random(32), $options['ttl'] ?? null);
        }
    }

    /**
     * @param  string  $name
     * @param  callable  $callback
     * @param  array{ttl?:  \DateTimeInterface|\DateInterval|int|null}  $options
     * @return mixed
     *
     * @throws \Throwable
     */
    public function step(string $name, callable $callback, $options = []): mixed
    {
        if ($this->state->hasCompletedStep($name)) {
            return $this->state->resultFor($name);
        }

        $this->eventDispatcher?->dispatch(new StepStarting($this->state, $name));

        try {
            $result = $callback();
        } catch (Throwable $e) {
            $this->eventDispatcher?->dispatch(new StepFailed($this->state, $name, $e));

            throw $e;
        }

        $this->state->recordStepResult($name, $result, $completedAt = Carbon::now()->getTimestamp());

        $this->eventDispatcher?->dispatch(new StepCompleted($this->state, $name, $result, $completedAt));

        $this->executionRepository->saveStep($this->state, $name, $options['ttl'] ?? $this->options['ttl'] ?? null);

        return $result;
    }

    /**
     * Delete the ExecutionState.
     *
     * @return void
     */
    public function delete()
    {
        $this->executionRepository->delete($this->state->id());
    }

    public function getState(): ExecutionState
    {
        return $this->state;
    }
}
