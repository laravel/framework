<?php

namespace Illuminate\Bus\ExecutionContext;

use Illuminate\Bus\Events\StepCompleted;
use Illuminate\Bus\Events\StepFailed;
use Illuminate\Bus\Events\StepStarting;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Workflow\ExecutionRepository as ExecutionRepositoryContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class ExecutionContext
{
    /**
     * The context's state.
     */
    protected ExecutionState $state;

    /**
     * @param  ExecutionRepositoryContract  $executionRepository
     * @param  Dispatcher|null  $eventDispatcher
     * @param  mixed|null  $id
     * @param  array{ttl?: \DateTimeInterface|\DateInterval|int|null|(\Closure(): \DateTimeInterface|\DateInterval|int|null)}|\Illuminate\Contracts\Support\Arrayable  $options
     */
    public function __construct(
        protected ExecutionRepositoryContract $executionRepository,
        protected ?Dispatcher $eventDispatcher = null,
        mixed $id = null,
        protected $options = [],
    ) {
        $this->options = $this->normalizeOptions($options);

        if ($id instanceof ExecutionState) {
            $this->state = $id;
        } else {
            $this->state = $this->executionRepository->find($id)
                ?? $this->executionRepository->create($id ?? Str::random(32), $this->options);
        }
    }

    /**
     * @template TReturn
     *
     * @param  string  $name
     * @param  (callable(): TReturn)  $callback
     * @param  array{ttl?:  \DateTimeInterface|\DateInterval|int|null|(\Closure(ExecutionStepResult): \DateTimeInterface|\DateInterval|int|null)}|\Illuminate\Contracts\Support\Arrayable  $options
     * @return TReturn
     *
     * @throws \Throwable
     */
    public function step(string $name, callable $callback, $options = []): mixed
    {
        if ($this->state->hasCompletedStep($name)) {
            return $this->state->resultFor($name);
        }

        $stepResult = $this->executionRepository->getStep($this->state, $name);

        if ($stepResult !== null) {
            $this->state->recordStepResult($stepResult);

            return $stepResult->result;
        }

        $this->eventDispatcher?->dispatch(new StepStarting($this->state, $name));
        try {
            $result = $callback();
        } catch (Throwable $e) {
            $this->eventDispatcher?->dispatch(new StepFailed($this->state, $name, $e));

            throw $e;
        }

        $stepResult = new ExecutionStepResult($this->state->id(), $name, Carbon::now()->getTimestamp(), $result);

        $this->state->recordStepResult($stepResult);
        $this->executionRepository->saveStep($this->state, $stepResult, $this->normalizeStepOptions($options, $stepResult));
        $this->eventDispatcher?->dispatch(new StepCompleted($this->state, $name, $stepResult));

        return $result;
    }

    /**
     * Forget an individual step so it can be reassessed.
     *
     * @param  string  $name
     * @return void
     */
    public function forgetStep(string $name): void
    {
        $this->executionRepository->deleteStep($this->state, $name);

        $this->state->forgetStep($name);
    }

    /**
     * Delete the ExecutionState.
     *
     * @return void
     */
    public function delete()
    {
        $this->executionRepository->delete($this->state);

        $this->state->clearSteps();
    }

    /**
     * Get the state of the ExecutionContext.
     *
     * @return ExecutionState
     */
    public function getState(): ExecutionState
    {
        return $this->state;
    }

    /**
     * @param  array<array-key, mixed>|\Illuminate\Contracts\Support\Arrayable  $options
     * @return array<array-key, mixed>
     */
    protected function normalizeOptions($options): array
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        if (array_key_exists('ttl', $options)) {
            $options['ttl'] = value($options['ttl']);
        }

        return $options;
    }

    /**
     * @param  array<array-key, mixed>|\Illuminate\Contracts\Support\Arrayable  $options
     * @param  ExecutionStepResult  $stepResult
     * @return array
     */
    protected function normalizeStepOptions($options, ExecutionStepResult $stepResult): array
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        if (array_key_exists('ttl', $options)) {
            $options['ttl'] = value($options['ttl'], $stepResult);
        }

        return $options;
    }
}
